<?php

namespace App\Service\Alarm\Validator\Standalone;

use App\Entity\ClientSetting;
use App\Entity\DeviceData;
use App\Service\Alarm\Types\Standalone\DeviceOffline;
use App\Service\Alarm\Validator\BaseAlarmHandler;

class DeviceOfflineChecker extends BaseAlarmHandler
{
    public function validate(DeviceData $lastDeviceData, ClientSetting $clientSetting): void
    {
        $type = new DeviceOffline();

        if ($clientSetting->isDeviceOfflineAlarmActive() === false) {
            $this->closeAlarm($lastDeviceData, $type);
            return;
        }

        $device = $lastDeviceData->getDevice();
        $xmlInterval = $device->getIntervalTrashholdInSeconds();

        if (!$xmlInterval) {
            throw new \RuntimeException(sprintf("Xml interval is required. Device with id %d, doesn't have interval set!", $device->getId()));
        }

        if (time() - $lastDeviceData->getDeviceDate()?->format('U') > $xmlInterval) {
            $this->createAlarm($lastDeviceData, $type);
        } else {
            $this->closeAlarm($lastDeviceData, $type);
        }
    }
}