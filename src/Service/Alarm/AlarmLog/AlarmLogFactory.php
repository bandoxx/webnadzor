<?php

namespace App\Service\Alarm\AlarmLog;

use App\Entity\DeviceAlarm;
use App\Entity\DeviceAlarmLog;

class AlarmLogFactory
{

    public function create(DeviceAlarm $deviceAlarm, string $recipient, string $type): DeviceAlarmLog
    {
        return (new DeviceAlarmLog())
            ->setClient($deviceAlarm->getDevice()->getClient())
            ->setDeviceAlarm($deviceAlarm)
            ->setNotifiedBy($type)
            ->setRecipient($recipient)
            ->setMessage($deviceAlarm->getShortMessage() . ' Intelteh D.O.O')
        ;
    }

}