<?php

namespace App\Service\Chart\Type\Device;

use App\Service\Chart\Type\BaseChartType;

class DeviceBatteryDataChart extends BaseChartType implements DeviceChartInterface
{

    public const KEY = 'device-battery';

    public function getType(): string
    {
        return self::KEY;
    }

    public function formatData(array $data): array
    {
        $result = [];

        foreach ($data as $deviceData) {
            $time = self::convertDateTimeToChartStamp($deviceData->getDeviceDate());

            $result['battery-level'][] = [$time, $deviceData->getBattery()];
            $result['battery-power'][] = [$time, (float) $deviceData->getVbat()];

        }

        return $result;
    }

}