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

    public function getLocationStringForDigitalEntry(Device $device, bool $onOff, int $sensor): string
    {
        $address = $device->getName();
        $sensorData = $device->getEntryData($sensor);

        $text = $onOff === true ? $sensorData['d_on_name'] : $sensorData['d_off_name'];

        return sprintf("Adresa: %s, Lokacija: %s, Digitalni ulaz: %s, Status: %s",
            $address,
            $sensorData['t_location'],
            $sensorData['d_name'],
            $text
        );
    }
}