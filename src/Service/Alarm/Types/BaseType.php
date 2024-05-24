<?php

namespace App\Service\Alarm\Types;

use App\Entity\Device;
use App\Entity\DeviceData;

class BaseType
{

    public function getLocationString(Device $device, ?int $sensor = null): string
    {
        $address = $device->getName();

        if (!$sensor) {
            return sprintf("Adresa: %s", $address);
        }

        $sensorData = $device->getEntryData($sensor);

        return sprintf("Adresa: %s, Lokacija: %s, Mjerno mjesto: %s",
            $address,
            $sensorData['t_location'],
            $sensorData['t_name']
        );
    }

    public function alarmActivatedString(DeviceData $deviceData): string
    {
        return sprintf("Alarm aktiviran: %s", $deviceData->getDeviceDate()->format('d.m.Y H:i:s'));
    }

}