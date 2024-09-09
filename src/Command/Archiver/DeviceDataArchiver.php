<?php

namespace App\Command\Archiver;

use App\Entity\DeviceDataArchive;
use App\Factory\DeviceDataArchiveFactory;
use App\Repository\DeviceDataRepository;
use App\Repository\DeviceRepository;
use App\Service\Archiver\ArchiverInterface;
use App\Service\Archiver\DeviceData\DeviceDataPDFArchiver;
use App\Service\Archiver\DeviceData\DeviceDataXLSXArchiver;
use App\Service\Exception\ExceptionFormatter;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:device-data-archiver',
    description: 'Command for making XLSX and PDF archives on daily and monthly level.',
)]
class DeviceDataArchiver extends Command
{
    public function __construct(
        private DeviceDataXLSXArchiver   $XLSXArchiver,
        private DeviceDataPDFArchiver    $PDFArchiver,
        private DeviceRepository         $deviceRepository,
        private DeviceDataRepository     $deviceDataRepository,
        private DeviceDataArchiveFactory $deviceDataArchiveFactory,
        private EntityManagerInterface   $entityManager
    )
    {
        parent::__construct();
    }
    protected function configure(): void
    {
        $this
            ->addOption('daily', null, InputOption::VALUE_NONE, 'Daily report')
            ->addOption('monthly', null, InputOption::VALUE_NONE, 'Monthly report')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln(sprintf("%s - %s started", (new \DateTime())->format('Y-m-d H:i:s'), $this->getName()));

        $date = (new \DateTime('-1 day'))->setTime(0, 0, 0);

        $daily = $input->getOption('daily');
        $monthly = $input->getOption('monthly');

        if (($daily || $monthly) === false) {
            $output->writeln("Daily or monthly report must be set!");

            return Command::FAILURE;
        }

        $devices = $this->deviceRepository->findAll();

        if ($daily) {
            foreach ($devices as $device) {
                $data = $this->deviceDataRepository->findByDeviceAndForDay($device, $date);

                foreach([1, 2] as $entry) {
                    $this->generateDailyReport($device, $data, $entry, $date);
                }
            }
        }

        if ($monthly) {
            foreach ($devices as $device) {
                $data = $this->deviceDataRepository->findByDeviceAndForMonth($device, $date);

                foreach([1, 2] as $entry) {
                    $this->generateMonthlyReport($device, $data, $entry, $date);
                }
            }
        }

        $output->writeln(sprintf("%s - %s finished successfully", (new \DateTime())->format('Y-m-d H:i:s'), $this->getName()));
        return Command::SUCCESS;
    }

    private function generateDailyReport($device, $data, $entry, $date): void
    {
        $fileName = $this->generateFilename($device->getXmlName(), $entry, $date->format(ArchiverInterface::DAILY_FILENAME_FORMAT));

        $this->XLSXArchiver->saveDaily($device, $data, $entry, $date, $fileName);
        $this->PDFArchiver->saveDaily($device, $data, $entry, $date, $fileName);

        $archive = $this->deviceDataArchiveFactory->create($device, $date, $entry, $fileName, DeviceDataArchive::PERIOD_DAY);

        $this->entityManager->persist($archive);
        $this->entityManager->flush();
    }

    private function generateMonthlyReport($device, $data, $entry, $date): void
    {
        $fileName = $this->generateFilename($device->getXmlName(), $entry, $date->format(ArchiverInterface::MONTHLY_FILENAME_FORMAT));
        $this->XLSXArchiver->saveMonthly($device,  $data, $entry, $date, $fileName);
        $this->PDFArchiver->saveMonthly($device, $data, $entry, $date, $fileName);

        $archive = $this->deviceDataArchiveFactory->create($device, $date, $entry, $fileName, DeviceDataArchive::PERIOD_MONTH);

        $this->entityManager->persist($archive);
        $this->entityManager->flush();
    }

    private function generateFilename(string $xmlName, $entry, $date): string
    {
        return sprintf('%s_t%s_%s', $xmlName, $entry, $date);
    }
}
