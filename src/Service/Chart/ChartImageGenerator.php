<?php

namespace App\Service\Chart;

use App\Entity\Device;
use App\Model\ChartType;
use Symfony\Component\Process\Process;

class ChartImageGenerator
{
    public function __construct(private ChartHandler $chartHandler, private string $projectDirectory) {}

    public function getTemperatureImageChartPath(): ?string
    {
        return null;
        //return sprintf("%s/archive/chart_temperature.jpg", $this->projectDirectory);
    }

    public function getHumidityImageChartPath(): ?string
    {
        return null;
        //return sprintf("%s/archive/chart_humidity.jpg", $this->projectDirectory);
    }

    public function generateTemperatureAndHumidityChartImage(Device $device, int $entry, \DateTime $fromDate, \DateTime $toDate): void
    {
        return;
        $this->generateDeviceChartImage($device, ChartType::TEMPERATURE, $entry, $fromDate, $toDate);
        $this->generateDeviceChartImage($device, ChartType::HUMIDITY, $entry, $fromDate, $toDate);
    }

    private function generateDeviceChartImage(Device $device, string $type, int $entry, \DateTime $fromDate, \DateTime $toDate): void
    {
        $fromTimestamp = $fromDate->getTimestamp() * 1000;
        $toTimestamp = $toDate->getTimestamp() * 1000;

        //generating chart image
        $chartData = $this->chartHandler->createDeviceDataChart($device, $type, $entry, $fromDate, $toDate);

        if (empty($chartData)) {
            return;
        }

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

        if ($type === ChartType::TEMPERATURE) {
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

        $config = [
            'chart' => [
                'zoomType' => 'x',
            ],
            'title' => [
                'text' => $type === 'temperature' ? 'Temperatura' : 'Relativna vlaga',
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
        $jsonConfig = json_encode($config, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);
        $jsonFilePath = sprintf("%s/archive/chartConfigData_%s.json", $root, $type);
        file_put_contents($jsonFilePath, $jsonConfig);

        //generating the image command
        $outputFilePath = sprintf("%s/archive/chart_%s.jpg", $root, $type);
        $command = ['php', 'bin/console', 'app:generate-image', $jsonFilePath, $outputFilePath];

        $process = new Process($command, $root);
        $process->setTimeout(1080); //18 mins timeout
        $process->mustRun();
    }
}