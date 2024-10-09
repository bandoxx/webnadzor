<?php

namespace App\Service\Chart\Type\Device;

use App\Service\Chart\Type\BaseChartType;

class DeviceSignalDataChart extends BaseChartType implements DeviceChartInterface
{

    public const KEY = 'device-signal';

    public function getType(): string
    {
        return self::KEY;
    }

    public function formatData(array $data): array
    {
        $result = [];

        foreach ($data as $deviceData) {
            $time = self::convertDateTimeToChartStamp($deviceData->getDeviceDate());

            $result['signal'][] = [$time, (float) $deviceData->getGsmSignal()];
        }

        return $result;
    }

}