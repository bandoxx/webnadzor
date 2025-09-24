<?php

namespace App\Command\Cache;

use App\Entity\Device;
use App\Entity\DeviceDataLastCache;
use App\Repository\DeviceDataLastCacheRepository;
use App\Repository\DeviceDataRepository;
use App\Repository\DeviceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:device-data-last-cache:populate',
    description: 'Populate DeviceDataLastCache for all devices and entries (1 and 2) using the latest available DeviceData records.'
)]
class PopulateDeviceDataLastCacheCommand extends Command
{
    public function __construct(
        private readonly DeviceRepository $deviceRepository,
        private readonly DeviceDataRepository $deviceDataRepository,
        private readonly DeviceDataLastCacheRepository $cacheRepository,
        private readonly EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $devices = $this->deviceRepository->findAll();
        $updated = 0;
        $created = 0;
        $processed = 0;

        foreach ($devices as $device) {
            foreach ([1, 2] as $entry) {
                $deviceData = $this->deviceDataRepository->findLastRecordForDeviceAndEntry($device, $entry);
                if (!$deviceData) {
                    continue;
                }

                // Only consider if this entry has a value (mirrors subscriber logic)
                $value = $deviceData->getT($entry);
                if ($value === null) {
                    continue;
                }

                $date = $deviceData->getDeviceDate();
                $existing = $this->cacheRepository->findOneBy(['device' => $device, 'entry' => $entry]);

                if ($existing) {
                    if ($existing->getDeviceDate() === null || $date >= $existing->getDeviceDate()) {
                        $existing
                            ->setDeviceData($deviceData)
                            ->setDeviceDate($date);
                        $updated++;
                    }
                } else {
                    $deviceRef = $this->em->getReference(Device::class, $device->getId());
                    $cache = (new DeviceDataLastCache())
                        ->setDevice($deviceRef)
                        ->setEntry($entry)
                        ->setDeviceData($deviceData)
                        ->setDeviceDate($date);
                    $this->em->persist($cache);
                    $created++;
                }

                $processed++;

                // Batch flush to avoid large unit of work
                if (($processed % 200) === 0) {
                    $this->em->flush();
                    $this->em->clear(DeviceDataLastCache::class);
                }
            }
        }

        $this->em->flush();

        $output->writeln(sprintf('Processed entries: %d, created: %d, updated: %d', $processed, $created, $updated));
        $output->writeln('Done.');

        return Command::SUCCESS;
    }
}
