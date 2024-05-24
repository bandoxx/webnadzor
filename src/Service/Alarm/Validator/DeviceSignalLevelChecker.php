<?php

namespace App\Service\Alarm\Validator;

use App\Entity\ClientSetting;
use App\Entity\DeviceData;
use App\Service\Alarm\AlarmHandlerInterface;
use App\Service\Alarm\Types\DeviceSignalLow;

class DeviceSignalLevelChecker extends BaseAlarmHandler implements AlarmHandlerInterface
{
    public function validate(DeviceData $deviceData, ClientSetting $clientSetting): void
    {
        if (!$clientSetting->isDeviceSignalAlarmActive()) {
            return;
        }

        $type = new DeviceSignalLow();

        if ($deviceData->getBattery() <= $clientSetting->getDeviceSignalAlarm()) {
            $this->createAlarm($deviceData, $type);
        } else {
            $this->closeAlarm($deviceData, $type);
        }
    }
}