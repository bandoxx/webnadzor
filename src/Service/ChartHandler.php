<?php

namespace App\Service;

use App\Entity\DeviceData;
use App\Entity\Device;
use App\Repository\DeviceDataRepository;
use App\Repository\DeviceRepository;

class ChartHandler
{

    public const TEMPERATURE = 'temperature';
    public const HUMIDITY = 'humidity';

    public function __construct(
        private readonly DeviceDataRepository $deviceDataRepository,
    ) {}

    public function createChart(Device $device, int $entry, string $type, ?\DateTime $fromDate = null, ?\DateTime $toDate = null): array
    {
        $data = $this->deviceDataRepository->getChartData(
            $device->getId(),
            $fromDate,
            $toDate
        );

        if ($type === self::TEMPERATURE) {
            return $this->createTemperatureChart($data, $device, $entry);
        }

        if ($type === self::HUMIDITY) {
            return $this->createHumidityChart($data, $device, $entry);
        }

        return [];
    }

    private function createTemperatureChart(array $data, Device $device, int $entry): array
    {
        $entryData = $device->getEntryData($entry);

        $result = [
            't' => [],
            'mkt' => [],
            'min' => $entryData['t_chart_min'] ?? null,
            'max' => $entryData['t_chart_max'] ?? null,
        ];

        foreach ($data as $row) {
            /** @var DeviceData $deviceData */
            $deviceData = $row[0];
            $time = self::convertDateTimeToChartStamp($deviceData->getDeviceDate());

            $result['t'][] = [$time, (float) $deviceData->getT($entry)];
            $result['mkt'][] = [$time, (float) $deviceData->getMkt($entry)];
        }

        return $result;
    }

    private function createHumidityChart(array $data, Device $device, int $entry): array
    {
        $entryData = $device->getEntryData($entry);

        $result = [
            'rh' => [],
            'min' => $entryData['rh_chart_min'] ?? null,
            'max' => $entryData['rh_chart_max'] ?? null
        ];

        foreach ($data as $row) {
            /** @var DeviceData $deviceData */
            $deviceData = $row[0];
            $time = self::convertDateTimeToChartStamp($deviceData->getDeviceDate());

            $result['rh'][] = [$time, (float) $deviceData->getRh($entry)];
        }

        return $result;
    }

    private static function convertDateTimeToChartStamp(\DateTimeInterface $dateTime): float
    {
        return floor($dateTime->getTimestamp() * 1000);
    }
}