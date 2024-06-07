<?php

namespace App\Service\Client;

use App\Entity\ClientSetting;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

class ClientSettingsUpdater
{

    public function __construct(private EntityManagerInterface $entityManager) {}

    public function update(ClientSetting $clientSetting, Request $request): void
    {
        $data = $request->request->all();

        $clientSetting
            ->setDeviceSignalAlarmActive(filter_var($data['is_device_signal_level_active'], FILTER_VALIDATE_BOOLEAN))
            ->setDeviceSignalAlarm($data['device_signal_level'])
            ->setDeviceOfflineAlarmActive(filter_var($data['is_device_offline_alarm_active'], FILTER_VALIDATE_BOOLEAN))
            ->setBatteryLevelAlarmActive(filter_var($data['is_battery_level_alarm_active'], FILTER_VALIDATE_BOOLEAN))
            ->setDeviceSensorErrorAlarmActive(filter_var($data['is_device_sensor_error_alarm_active'], FILTER_VALIDATE_BOOLEAN))
            ->setIsTemperatureAlarmActive(filter_var($data['is_temperature_alarm_active'], FILTER_VALIDATE_BOOLEAN))
            ->setIsHumidityAlarmActive(filter_var($data['is_humidity_alarm_active'], FILTER_VALIDATE_BOOLEAN))
            ->setBatteryLevelAlert($data['battery_level_alarm'])
        ;

        $emailList = [];

        for ($i = 1; $i <= 5; $i++) {
            $key = "email_list$i";

            if (!$data[$key]) {
                continue;
            }

            $emailList[] = $data[$key];
        }

        $emailList = array_values(array_unique($emailList));

        $clientSetting->setAlarmNotificationList($emailList);

        $this->entityManager->flush();
    }
}