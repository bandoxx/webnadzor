<?php

namespace App\Service;

use App\Entity\DeviceData;
use App\Entity\Device;
use App\Repository\DeviceRepository;

class ChartHandler
{

    public const TEMPERATURE = 'temperature';
    public const HUMIDITY = 'humidity';

    public function __construct(private DeviceRepository $deviceRepository) {}

    public function createChart(string $type, array $data, int $deviceId, int $entry): ?array
    {
        if ($type === self::TEMPERATURE) {
            return $this->createTemperatureChart($data, $deviceId, $entry);
        }

        if ($type === self::HUMIDITY) {
            return $this->createHumidityChart($data, $deviceId, $entry);
        }

        return null;
    }

    private function createTemperatureChart(array $data, int $deviceId, int $entry): array
    {
        $device = $this->deviceRepository->find($deviceId);
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

    private function createHumidityChart(array $data, int $deviceId, int $entry): array
    {
        $device = $this->deviceRepository->find($deviceId);
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