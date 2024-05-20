<?php

namespace App\Service\Alarm\Validator;

use App\Entity\ClientSetting;
use App\Entity\DeviceData;
use App\Service\Alarm\AlarmHandlerInterface;

class BatteryLevelChecker extends BaseAlarmHandler implements AlarmHandlerInterface
{
    public function validate(DeviceData $deviceData, ClientSetting $clientSetting): void
    {
        if (!$clientSetting->isBatteryLevelAlarmActive()) {
            return;
        }

        $this->alarmShouldBeOn = false;
        $alarm = $this->findAlarm($deviceData->getDevice(), AlarmHandlerInterface::BATTERY_LEVEL);

        if ($deviceData->getBattery() <= $clientSetting->getBatteryLevelAlert()) {
            $this->alarmShouldBeOn = true;
        }

        $this->finish($alarm, $deviceData, AlarmHandlerInterface::BATTERY_LEVEL);
    }

}