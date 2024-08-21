<?php

declare(strict_types=1);

namespace App\Command;

use App\Repository\DeviceDataRepository;
use App\Repository\DeviceRepository;
use App\Service\Alarm\Validator\Standalone\DeviceOfflineChecker;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:device:offline-checker', description: 'Alarm activator/deactivator for offline devices')]
class DeviceOfflineCheckerCommand extends Command
{
    public function __construct(
        private DeviceRepository $deviceRepository,
        private DeviceDataRepository $deviceDataRepository,
        private DeviceOfflineChecker $checker
    )
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln(sprintf("%s - %s started", (new \DateTime())->format('Y-m-d H:i:s'), $this->getName()));

        $devices = $this->deviceRepository->findActiveDevices();

        foreach ($devices as $device) {
            $settings = $device->getClient()?->getClientSetting();
            $deviceData = $this->deviceDataRepository->getLastRecord($device->getId());

            if ($deviceData === null || $settings === null) {
                continue;
            }

            $this->checker->validate($deviceData, $settings);
        }

        $output->writeln(sprintf("%s - %s finished successfully", (new \DateTime())->format('Y-m-d H:i:s'), $this->getName()));

        return Command::SUCCESS;
    }
}
