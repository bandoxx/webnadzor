<?php

namespace App\Service\Alarm\Validator;

use App\Entity\ClientSetting;
use App\Entity\DeviceData;
use App\Service\Alarm\AlarmHandlerInterface;

class DeviceSignalLevelChecker extends BaseAlarmHandler implements AlarmHandlerInterface
{
    public function validate(DeviceData $deviceData, ClientSetting $clientSetting): void
    {
        if (!$clientSetting->isDeviceSignalAlarmActive()) {
            return;
        }

        $this->alarmShouldBeOn = false;
        $alarm = $this->findAlarm($deviceData->getDevice(), AlarmHandlerInterface::SIGNAL_LEVEL);

        if ($deviceData->getBattery() <= $clientSetting->getDeviceSignalAlarm()) {
            $this->alarmShouldBeOn = true;
        }

        $this->finish($alarm, $deviceData, AlarmHandlerInterface::SIGNAL_LEVEL);
    }

}