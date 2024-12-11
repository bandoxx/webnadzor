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
use Symfony\Component\Process\Process;

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
                        $this->generateJsonAndChartImage($device, $entry, $date, 'humidity');
                        $this->generateJsonAndChartImage($device, $entry, $date, 'temperature');
                        $this->generateDailyReport($device, $data, $entry, $date);
                    }
                }
            }

            if ($monthly) {
                foreach ($devices as $device) {
                    $data = $this->deviceDataRepository->findByDeviceAndForMonth($device, $date);

                    foreach([1, 2] as $entry) {
                        $this->generateJsonAndChartImage($device, $entry, $date, 'humidity', "monthly");
                        $this->generateJsonAndChartImage($device, $entry, $date, 'temperature', "monthly");
                        $this->generateMonthlyReport($device, $data, $entry, $date);
                    }
                }
            }
        }

        $output->writeln(sprintf("%s - %s finished successfully", (new \DateTime())->format('Y-m-d H:i:s'), $this->getName()));
        return Command::SUCCESS;
    }

    private function generateJsonAndChartImage(Device $device, $entry, \DateTime $dateTime, $type, $dateType = "daily"): void
    {
        $start = null;
        $end = null;

        if ($dateType === "daily") {
            $start = (clone $dateTime)->setTime(0, 0);
            $end = (clone $dateTime)->setTime(23, 59);
        } elseif ($dateType === "monthly") {
            $start = (clone $dateTime)->modify('first day of this month')->setTime(0, 0);
            $end = (clone $dateTime)->modify('last day of this month')->setTime(23, 59);
        }

        $fromTimestamp = $start->getTimestamp() * 1000;
        $toTimestamp = $end->getTimestamp() * 1000;

        //generating chart image
        $chartData = $this->chartHandler->createDeviceDataChart($device, $type, $entry, $start, $end);

        $plots = [];
        if (isset($chartData['min']) && is_numeric($chartData['min'])) {
            $plots[] = [
                'color' => 'white',
                'width' => 2,
                'value' => $chartData['min'],
                'label' => [
                    'style' => [
                        'color' => 'white',
                    ],
                    'text' => 'Minimum',
                    'align' => 'right',
                    'x' => -10,
                    'y' => 12,
                ],
            ];
        }

        if (isset($chartData['max']) && is_numeric($chartData['max'])) {
            $plots[] = [
                'color' => 'white',
                'width' => 2,
                'value' => $chartData['max'],
                'label' => [
                    'style' => [
                        'color' => 'white',
                    ],
                    'text' => 'Maksimum',
                    'align' => 'right',
                    'x' => -10,
                ],
            ];
        }

        if ($type === 'temperature') {
            $navigationSeries = [
                'data' => $chartData['t'],
                'name' => 'Temperatura',
            ];

            $series = [
                [
                    'name' => 'Temperatura',
                    'data' => $chartData['t'],
                ],
                [
                    'name' => 'MKT',
                    'data' => $chartData['mkt'],
                    'color' => '#e20074',
                ],
            ];
        } else {
            $navigationSeries = [
                'data' => $chartData['rh'],
                'name' => 'Vlaznost',
            ];

            $series = [
                [
                    'name' => 'Vlaznost',
                    'data' => $chartData['rh'],
                ],
            ];
        }

        //generate the final chart configuration
        $chartConfig = [
            'chart' => [
                'zoomType' => 'x',
            ],
            'title' => [
                'text' => $type === 'temperature' ? 'Temperatura' : 'Vlaga',
            ],
            'time' => [
                'timezone' => 'Europe/Zagreb',
            ],
            'navigator' => [
                'adaptToUpdatedData' => false,
                'series' => $navigationSeries,
            ],
            'rangeSelector' => [
                'enabled' => false,
            ],
            'scrollbar' => [
                'liveRedraw' => false,
            ],
            'xAxis' => [
                'type' => 'datetime',
                'min' => $fromTimestamp,
                'max' => $toTimestamp,
                'dateTimeLabelFormats' => [
                    'millisecond' => '%H:%M:%S.%L',
                    'second' => '%H:%M:%S',
                    'minute' => '%H:%M',
                    'hour' => '%H:%M',
                    'day' => '%e. %b',
                    'week' => '%e. %b',
                    'month' => '%b \'%y',
                    'year' => '%Y',
                ],
            ],
            'yAxis' => [
                'plotLines' => $plots,
                'title' => [
                    'text' => '',
                    'rotation' => 0,
                ],
            ],
            'legend' => [
                'enabled' => false,
            ],
            'series' => $series,
            'tooltip' => [
                'valueDecimals' => 2,
            ],
            'dataGrouping' => [
                'enabled' => false,
            ],
        ];

        $root  = $this->projectDirectory;
        $jsonConfig = json_encode($chartConfig, JSON_PRETTY_PRINT);
        $jsonFilePath = $root . '/chartConfigData_' . $type . '.json';
        file_put_contents($jsonFilePath, $jsonConfig);

        //generating the image command
        $outputFilePath = $root  . '/chart_' . $type . '.png';
        $command = ['php', 'bin/console', 'app:generate-image', $jsonFilePath, $outputFilePath];

        $process = new Process($command, $root);
        $process->mustRun();
    }

    private function generateDailyReport($device, $data, $entry, $date): void
    {
        $fileName = $this->generateFilename($device->getXmlName(), $entry, $date->format(ArchiverInterface::DAILY_FILENAME_FORMAT));

        $this->XLSXArchiver->saveDaily($device, $data, $entry, $date, $fileName);
        $archive = $this->PDFArchiver->saveDaily($device, $data, $entry, $date, $fileName);

//        $this->rawDataHandler->encrypt($this->deviceDataRawDataFactory->create($data, $entry, $date), $archive->getFullPathWithoutExtension());

        $archive = $this->deviceDataArchiveFactory->create($device, $date, $entry, $fileName, DeviceDataArchive::PERIOD_DAY);

        $this->entityManager->persist($archive);
        $this->entityManager->flush();
    }

    private function generateMonthlyReport($device, $data, $entry, $date): void
    {
        $fileName = $this->generateFilename($device->getXmlName(), $entry, $date->format(ArchiverInterface::MONTHLY_FILENAME_FORMAT));

        $this->XLSXArchiver->saveMonthly($device,  $data, $entry, $date, $fileName);
        $archive = $this->PDFArchiver->saveMonthly($device, $data, $entry, $date, $fileName);

//        $this->rawDataHandler->encrypt($this->deviceDataRawDataFactory->create($data, $entry, $date), $archive->getFullPathWithoutExtension());

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
