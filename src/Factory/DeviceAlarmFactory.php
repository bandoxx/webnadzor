<?php

namespace App\Factory;

use App\Entity\DeviceAlarm;
use App\Entity\DeviceData;
use App\Service\Alarm\Types\AlarmTypeInterface;

class DeviceAlarmFactory
{

    public function create(DeviceData $deviceData, AlarmTypeInterface $alarmType, ?int $sensor = null): DeviceAlarm
    {
        $alarm = new DeviceAlarm();

        $alarm->setDevice($deviceData->getDevice())
            ->setServerDate(new \DateTime())
            ->setDeviceDate($deviceData->getDeviceDate())
            ->setType($alarmType->getType())
            ->setSensor($sensor)
            ->setDeviceData($deviceData)
            ->setMessage($alarmType->getMessage($deviceData, $sensor))
            ->setShortMessage($alarmType->getShortMessage($deviceData, $sensor))
        ;

        return $alarm;
    }
}