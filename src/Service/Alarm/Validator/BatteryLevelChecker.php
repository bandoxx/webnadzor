<?php

namespace App\Service\Alarm\Validator;

use App\Entity\ClientSetting;
use App\Entity\DeviceData;
use App\Service\Alarm\AlarmHandlerInterface;
use App\Service\Alarm\Types\DeviceBatteryLow;

class BatteryLevelChecker extends BaseAlarmHandler implements AlarmHandlerInterface
{
    public function validate(DeviceData $deviceData, ClientSetting $clientSetting): void
    {
        if (!$clientSetting->isBatteryLevelAlarmActive()) {
            return;
        }

        $type = new DeviceBatteryLow();

        if ($deviceData->getBattery() <= $clientSetting->getBatteryLevelAlert()) {
            $this->createAlarm($deviceData, $type);
        } else {
            $this->closeAlarm($deviceData, $type);
        }
    }
}