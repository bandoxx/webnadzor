<?php

namespace App\Factory;

use App\Entity\DeviceAlarm;
use App\Entity\DeviceData;

class DeviceAlarmFactory
{

    public function create(DeviceData $deviceData, string $alarmType, ?int $sensor = null): DeviceAlarm
    {
        $alarm = new DeviceAlarm();

        $alarm->setDevice($deviceData->getDevice())
            ->setServerDate(new \DateTime())
            ->setDeviceDate($deviceData->getDeviceDate())
            ->setType($alarmType)
            ->setSensor($sensor)
        ;

        return $alarm;
    }

}