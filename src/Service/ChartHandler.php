<?php

namespace App\Service;

use App\Entity\DeviceData;

class ChartHandler
{

    public const TEMPERATURE = 'temperature';
    public const HUMIDITY = 'humidity';

    public function createChart(string $type, array $data, int $entry): ?array
    {
        if ($type === self::TEMPERATURE) {
            return $this->createTemperatureChart($data, $entry);
        }

        if ($type === self::HUMIDITY) {
            return $this->createHumidityChart($data, $entry);
        }

        return null;
    }

    private function createTemperatureChart(array $data, int $entry): array
    {
        $result = [
            't' => [],
            'mkt' => []
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

    private function createHumidityChart(array $data, int $entry): array
    {
        $result = [
            'rh' => [],
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