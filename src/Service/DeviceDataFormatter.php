<?php

namespace App\Service;

use App\Entity\Device;
use App\Entity\DeviceData;

class DeviceDataFormatter
{

    /**
     * @param Device $device
     * @param array<DeviceData> $deviceData
     * @param $entry
     * @return array
     */
    public function getTable(Device $device, array $deviceData, $entry): array
    {
        $table = [];

        $deviceEntryData = $device->getEntryData($entry);

        $tUnit = $deviceEntryData['t_unit'];
        $rhUnit = $deviceEntryData['rh_unit'];
        $i = 0;
        foreach ($deviceData as $data) {

            $table[] = [
                'br' => ++$i,
                'date' => $data->getDeviceDate()->format('d.m.Y H:i:s'),
                'note' => $data->getNote($entry),
                'temp' => sprintf("%s %s", $data->getT($entry), $tUnit),
                'max' => sprintf("%s %s", $data->getTMax($entry), $tUnit),
                'min' => sprintf("%s %s", $data->getTMin($entry), $tUnit),
                'avrg' => sprintf("%s %s", $data->getTAvrg($entry), $tUnit),
                'rh' => sprintf("%s %s", $data->getRh($entry), $rhUnit)
            ];
        }

        return $table;
    }

}