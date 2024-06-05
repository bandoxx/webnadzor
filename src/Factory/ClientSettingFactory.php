<?php

namespace App\Factory;

use App\Entity\Client;
use App\Entity\ClientSetting;

class ClientSettingFactory
{

    public function create(Client $client): ClientSetting
    {
        $clientSettings = new ClientSetting();

        $clientSettings->setClient($client)
            ->setAlarmNotificationList([])
            ->setBatteryLevelAlarmActive(true)
            ->setBatteryLevelAlert(30)
            ->setDeviceSignalAlarmActive(true)
            ->setDeviceSignalAlarm(30)
            ->setDeviceOfflineAlarmActive(true)
            ->setDeviceSensorErrorAlarmActive(true)
        ;

        return $clientSettings;
    }

}