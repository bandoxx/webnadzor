<?php

namespace App\Service\Chart\Type\DeviceData;

use App\Entity\Device;
use App\Entity\DeviceData;
use App\Service\Chart\Type\BaseChartType;

class HumidityType extends BaseChartType implements DeviceDataChartInterface
{

    public const KEY = 'humidity';

    public function getType(): string
    {
        return self::KEY;
    }

    public function formatData(array $data, Device $device, ?int $entry = null): array
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

}