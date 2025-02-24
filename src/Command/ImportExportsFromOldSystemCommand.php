<?php

declare(strict_types=1);

namespace App\Command;

use App\Repository\DeviceDataArchiveRepository;
use App\Repository\DeviceRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'ImportExportsFromOldSystemCommand',
    description: 'Imports exports from the system on the old server while checking for data',
)]
class ImportExportsFromOldSystemCommand extends Command
{
    public function __construct(
        private DeviceRepository $deviceRepository,
        private DeviceDataArchiveRepository $deviceDataArchiveRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('clientId', InputArgument::OPTIONAL, 'The client Id');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $clientId = (int) $input->getArgument('clientId');
        $output->writeln(sprintf("%s - %s started", (new \DateTime())->format('Y-m-d H:i:s'), $this->getName()));

        $devices = $this->deviceRepository->findDevicesByClient($clientId);

        foreach ($devices as $device) {
            $deviceDataDaily = $this->deviceDataArchiveRepository->getDailyArchives($device, 2);
            $deviceDataMonthly = $this->deviceDataArchiveRepository->getMonthlyArchives($device, 2);

            foreach ($deviceDataDaily as $deviceData) {
                $this->checkIfDeviceExists($deviceData, $clientId, $output, 'daily');
            }

            foreach ($deviceDataMonthly as $deviceData) {
                $this->checkIfDeviceExists($deviceData, $clientId, $output, 'monthly');
            }
        }

        $output->writeln(sprintf("%s - %s finished successfully", (new \DateTime())->format('Y-m-d H:i:s'), $this->getName()));

        return Command::SUCCESS;
    }

    private function checkIfDeviceExists($deviceData, $clientId, OutputInterface $output, string $type): void
    {
        $filePath = sprintf(
            "archive/%s/%s/%s/%s/%s",
            $clientId,
            $type,
            ($deviceData->getArchiveDate())->format('Y'),
            ($deviceData->getArchiveDate())->format('m'),
            ($deviceData->getArchiveDate())->format('d')
        );

        $fullFilePath = sprintf("%s/%s", $filePath, $deviceData->getFilename());

        if (!file_exists($fullFilePath)) {
            $output->writeln(sprintf("<error>File does not exist: %s</error>", $fullFilePath));
        }
    }
}
