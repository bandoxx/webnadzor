<?php

namespace App\Service;

use App\Entity\DeviceData;

class ChartHandler
{

    public const TEMPERATURE = 'temperature';
    public const HUMIDITY = 'humidity';

    public function createChart($type, $data, $entry)
    {
        if ($type === self::TEMPERATURE) {
            return $this->createTemperatureChart($data, $entry);
        }

        if ($type === self::HUMIDITY) {
            return $this->createHumidityChart($data, $entry);
        }

        return null;
    }

    private function createTemperatureChart($data, $entry)
    {
        $result = [
            't' => [],
            'mkt' => []
        ];

        foreach ($data as $row) {
            /** @var DeviceData $deviceData */
            $deviceData = $row[0];
            $time = self::convertDateTimeToChartStamp($deviceData->getDeviceDate());

            $getter = "entry$entry";
            $result['t'][] = [$time, (float) $deviceData->getT($entry)];
            $result['mkt'][] = [$time, (float) $deviceData->getMkt($entry)];
        }

        return $result;
    }

    private function createHumidityChart($data, $entry)
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

    private static function convertDateTimeToChartStamp(\DateTime $dateTime)
    {
        return floor($dateTime->getTimestamp() * 1000);
    }
}