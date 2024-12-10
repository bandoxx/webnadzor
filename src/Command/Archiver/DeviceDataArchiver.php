<?php

namespace App\Command\Archiver;

use App\Entity\Device;
use App\Entity\DeviceDataArchive;
use App\Factory\DeviceDataArchiveFactory;
use App\Repository\DeviceDataRepository;
use App\Repository\DeviceRepository;
use App\Service\Archiver\ArchiverInterface;
use App\Service\Archiver\DeviceData\DeviceDataPDFArchiver;
use App\Service\Archiver\DeviceData\DeviceDataXLSXArchiver;
use App\Service\Chart\ChartHandler;
use App\Service\RawData\Factory\DeviceDataRawDataFactory;
use App\Service\RawData\RawDataHandler;
use Doctrine\ORM\EntityManagerInterface;
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
        private RawDataHandler           $rawDataHandler,
        private DeviceDataRawDataFactory $deviceDataRawDataFactory,
        private EntityManagerInterface   $entityManager,
        private ChartHandler             $chartHandler,
        private string                   $projectDirectory
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('daily', null, InputOption::VALUE_NONE, 'Daily report')
            ->addOption('monthly', null, InputOption::VALUE_NONE, 'Monthly report')
            ->addOption('fromDate', null, InputOption::VALUE_OPTIONAL, 'From date', null)
            ->addOption('toDate', null, InputOption::VALUE_OPTIONAL, 'To date', null)
            ->addOption('deviceId', null, InputOption::VALUE_OPTIONAL, 'Device id', null)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln(sprintf("%s - %s started", (new \DateTime())->format('Y-m-d H:i:s'), $this->getName()));

        $daily = $input->getOption('daily');
        $monthly = $input->getOption('monthly');

        if (($daily || $monthly) === false) {
            $output->writeln("Daily or monthly report must be set!");

            return Command::FAILURE;
        }

        if ($deviceId = $input->getOption('deviceId')) {
            $devices[] = $this->deviceRepository->find($deviceId);
        } else {
            $devices = $this->deviceRepository->findAll();
        }

        $dates = $this->getDates($input->getOption('fromDate'), $input->getOption('toDate'));

        foreach ($dates as $date) {
            if ($daily) {
                foreach ($devices as $device) {
                    $data = $this->deviceDataRepository->findByDeviceAndForDay($device, $date);
                    foreach([1, 2] as $entry) {
                        $this->generateJsonAndChartImage($device, $entry, $date);
                        $this->generateDailyReport($device, $data, $entry, $date);
                    }
                }
            }

            if ($monthly) {
                foreach ($devices as $device) {
                    $data = $this->deviceDataRepository->findByDeviceAndForMonth($device, $date);

                    foreach([1, 2] as $entry) {
                        $this->generateJsonAndChartImage($device, $entry, $date);
                        $this->generateMonthlyReport($device, $data, $entry, $date);
                    }
                }
            }
        }

        $output->writeln(sprintf("%s - %s finished successfully", (new \DateTime())->format('Y-m-d H:i:s'), $this->getName()));
        return Command::SUCCESS;
    }

    private function generateJsonAndChartImage(Device $device, $entry, \DateTime $dateTime): void
    {
        $start = (clone ($dateTime))->setTime(0, 0);
        $end = (clone ($dateTime))->setTime(23, 59);
        //generating chart image
        $chartDataHum = $this->chartHandler->createDeviceDataChart($device, "humidity", $entry, $start, $end);
        $chartDataTemp = $this->chartHandler->createDeviceDataChart($device, "temperature", $entry, $start, $end);

        //creating json for chart
        $chartConfig = [
            'chart' => [
                'type' => 'line',
            ],
            'title' => [
                'text' => 'Primer grafikona',
            ],
            'xAxis' => [
                'categories' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            ],
            'yAxis' => [
                'title' => [
                    'text' => 'Brojke',
                ],
            ],
            'series' => [
                [
                    'name' => 'Serija 1',
                    'data' => [1, 3, 2, 4, 6, 5],
                ],
                [
                    'name' => 'Serija 2',
                    'data' => [2, 4, 3, 5, 7, 6],
                ],
            ],
        ];

        $root  = $this->projectDirectory;
        $jsonConfig = json_encode($chartConfig, JSON_PRETTY_PRINT);
        $jsonFilePath = $root . '/chartConfigData.json';
        file_put_contents($jsonFilePath, $jsonConfig);

        //generating the image command
//                $outputFilePath = $root  . '/chart.png';
//                $command = ['php', 'bin/console', 'app:generate-image', $jsonFilePath, $outputFilePath];
//
//                $process = new Process($command, $root);
//                $process->mustRun();
    }

    private function generateDailyReport($device, $data, $entry, $date): void
    {
        $fileName = $this->generateFilename($device->getXmlName(), $entry, $date->format(ArchiverInterface::DAILY_FILENAME_FORMAT));

        $this->XLSXArchiver->saveDaily($device, $data, $entry, $date, $fileName);
        $archive = $this->PDFArchiver->saveDaily($device, $data, $entry, $date, $fileName);

        $this->rawDataHandler->encrypt($this->deviceDataRawDataFactory->create($data, $entry, $date), $archive->getFullPathWithoutExtension());

        $archive = $this->deviceDataArchiveFactory->create($device, $date, $entry, $fileName, DeviceDataArchive::PERIOD_DAY);

        $this->entityManager->persist($archive);
        $this->entityManager->flush();
    }

    private function generateMonthlyReport($device, $data, $entry, $date): void
    {
        $fileName = $this->generateFilename($device->getXmlName(), $entry, $date->format(ArchiverInterface::MONTHLY_FILENAME_FORMAT));

        $this->XLSXArchiver->saveMonthly($device,  $data, $entry, $date, $fileName);
        $archive = $this->PDFArchiver->saveMonthly($device, $data, $entry, $date, $fileName);

        $this->rawDataHandler->encrypt($this->deviceDataRawDataFactory->create($data, $entry, $date), $archive->getFullPathWithoutExtension());

        $archive = $this->deviceDataArchiveFactory->create($device, $date, $entry, $fileName, DeviceDataArchive::PERIOD_MONTH);

        $this->entityManager->persist($archive);
        $this->entityManager->flush();
    }

    private function generateFilename(string $xmlName, $entry, $date): string
    {
        return sprintf('%s_t%s_%s', $xmlName, $entry, $date);
    }

    private function getDates(?string $fromDate = null, ?string $toDate = null): \DatePeriod
    {
        if ($fromDate === null) {
            $fromDate = new \DateTime('-1 day');
            $toDate = new \DateTime();
        } else if ($toDate === null) {
            $fromDate = new \DateTime($fromDate);
            $toDate = new \DateTime($toDate);
        } else {
            $fromDate = new \DateTime($fromDate);
            $toDate = new \DateTime();
        }

        $fromDate->setTime(0, 0, 0);
        $toDate->setTime(0, 0, 0);

        return new \DatePeriod(
            $fromDate,
            new \DateInterval('P1D'),
            $toDate
        );
    }
}
