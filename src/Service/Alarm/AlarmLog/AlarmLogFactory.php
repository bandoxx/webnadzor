<?php

namespace App\Service\Alarm\AlarmLog;

use App\Entity\Client;
use App\Entity\DeviceAlarm;
use App\Entity\DeviceAlarmLog;

class AlarmLogFactory
{

    public function create(Client $client, DeviceAlarm $deviceAlarm, string $type): DeviceAlarmLog
    {
        return (new DeviceAlarmLog())
            ->setClient($client)
            ->setDeviceAlarm($deviceAlarm)
            ->setNotifiedBy($type)
        ;
    }

}