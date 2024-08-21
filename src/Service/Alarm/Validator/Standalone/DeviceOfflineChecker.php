<?php

namespace App\Service\Alarm\Validator\Standalone;

use App\Entity\ClientSetting;
use App\Entity\DeviceData;
use App\Service\Alarm\Types\Standalone\DeviceOffline;
use App\Service\Alarm\Validator\BaseAlarmHandler;

class DeviceOfflineChecker extends BaseAlarmHandler
{
    public function validate(DeviceData $deviceData, ClientSetting $clientSetting): void
    {
        $type = new DeviceOffline();

        if ($clientSetting->isDeviceOfflineAlarmActive() === false) {
            $this->closeAlarm($deviceData, $type);
            return;
        }

        if (time() - $deviceData->getDeviceDate()?->format('U') > 5400) {
            $this->createAlarm($deviceData, $type);
        } else {
            $this->closeAlarm($deviceData, $type);
        }
    }
}