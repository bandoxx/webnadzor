<?php

namespace App\Service\Chart\Type\DeviceData;

use App\Entity\Device;
use App\Entity\DeviceData;
use App\Service\Chart\Type\BaseChartType;

class TemperatureType extends BaseChartType implements DeviceDataChartInterface
{

    public const TYPE = 'temperature';

    public function getType(): string
    {
        return self::TYPE;
    }

    public function formatData(array $data, Device $device, ?int $entry = null): array
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

}