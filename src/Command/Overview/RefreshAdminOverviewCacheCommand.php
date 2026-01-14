<?php

namespace App\Command\Overview;

use App\Entity\AdminOverviewCache;
use App\Entity\Device;
use App\Entity\User;
use App\Repository\AdminOverviewCacheRepository;
use App\Repository\ClientRepository;
use App\Repository\DeviceAlarmRepository;
use App\Repository\DeviceDataRepository;
use App\Repository\DeviceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[AsCommand(
    name: 'app:refresh-admin-overview-cache',
    description: 'Precompute and cache Admin Overview metrics for all active clients (for cron: run every 30-60 seconds)'
)]
class RefreshAdminOverviewCacheCommand extends Command
{
    public function __construct(
        private readonly ClientRepository $clientRepository,
        private readonly DeviceRepository $deviceRepository,
        private readonly DeviceDataRepository $deviceDataRepository,
        private readonly DeviceAlarmRepository $deviceAlarmRepository,
        private readonly AdminOverviewCacheRepository $cacheRepository,
        private readonly EntityManagerInterface $em,
        private readonly UrlGeneratorInterface $router,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $clients = $this->clientRepository->findAllActive();
        $now = new \DateTimeImmutable();
        $count = 0;

        foreach ($clients as $client) {
            $clientId = $client->getId();
            $devices = $this->deviceRepository->findDevicesByClient($clientId);

            $totalUsedSensors = 0;
            $onlineSensors = 0;
            $offlineSensors = 0;
            $alarmMessages = [];

            foreach ($devices as $device) {
                foreach (Device::SENSOR_ENTRIES as $sensor) {
                    $output->writeln(sprintf("%d %d", $device->getId(), $sensor));
                    $deviceData = $this->deviceDataRepository->findLastRecordForDeviceAndEntry($device, $sensor);
                    if (!$deviceData) {
                        continue;
                    }

                    $isDeviceOnline = time() - $deviceData->getDeviceDate()->getTimestamp() < $device->getIntervalThresholdInSeconds();

                    if ($device->isTUsed($sensor) || $device->isRhUsed($sensor) || $device->isDUsed($sensor)) {
                        $totalUsedSensors++;
                        if ($isDeviceOnline) {
                            $onlineSensors++;
                        } else {
                            $offlineSensors++;
                        }
                    }
                }

                $alarmsCount = $this->deviceAlarmRepository->findNumberOfActiveAlarmsForDevice($device);
                if ($alarmsCount) {
                    $activeAlarms = $this->deviceAlarmRepository->findActiveAlarms($device);
                    foreach ($activeAlarms as $alarm) {
                        $path = null;
                        if ($alarm->getSensor()) {
                            // Generate relative path to avoid needing host context in CLI
                            $path = sprintf("<a href='%s'><b><u>Link do alarma</u></b></a>",
                                $this->router->generate('app_alarm_list', [
                                    'clientId' => $clientId,
                                    'id' => $device->getId(),
                                    'entry' => $alarm->getSensor(),
                                ], UrlGeneratorInterface::ABSOLUTE_PATH)
                            );
                        }

                        if ($path) {
                            $alarmMessages[] = sprintf("%s %s - %s", $alarm->getMessage(), $alarm->getTimeString(), $path);
                        } else {
                            $alarmMessages[] = trim(($alarm->getMessage() ?? '') . ' ' . $alarm->getTimeString());
                        }
                    }
                }
            }

            $cache = $this->cacheRepository->findOneByClient($client) ?? new AdminOverviewCache();
            $cache->setClient($client)
                ->setNumberOfDevices($totalUsedSensors)
                ->setOnlineDevices($onlineSensors)
                ->setOfflineDevices($offlineSensors)
                ->setAlarms($alarmMessages)
                ->setUpdatedAt(\DateTime::createFromImmutable($now));

            $this->em->persist($cache);

            // Batch flush every 50 clients to keep memory down
            if ((++$count % 50) === 0) {
                $this->em->flush();
                $this->em->clear(AdminOverviewCache::class);
            }
        }

        $this->em->flush();

        $output->writeln(sprintf('Refreshed cache for %d clients at %s', count($clients), $now->format('Y-m-d H:i:s')));
        $output->writeln('Cron example: * * * * * /usr/bin/php /path/to/bin/console app:refresh-admin-overview-cache > /dev/null 2>&1');

        return Command::SUCCESS;
    }
}
