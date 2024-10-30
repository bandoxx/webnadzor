<?php

namespace App\Service\Alarm\Validator;

use App\Entity\ClientSetting;
use App\Entity\Device;
use App\Entity\DeviceData;
use App\Service\Alarm\AlarmHandlerInterface;
use App\Service\Alarm\Types\DigitalEntryStatus;

class DigitalEntryChecker extends BaseAlarmHandler implements AlarmHandlerInterface
{
    public function validate(DeviceData $deviceData, ClientSetting $clientSetting): void
    {
        /** @var Device $device */
        $device = $deviceData->getDevice();
        $type = new DigitalEntryStatus();

        if (!$clientSetting->getIsDigitalEntryAlarmActive()) {
            $this->closeAlarm($deviceData, $type);
            return;
        }

        $deviceEntryAlarms = $device->getDeviceAlarmSetupEntries()->toArray();

        foreach (range(1, 2) as $sensor) {
            foreach ($deviceEntryAlarms as $deviceEntryAlarm) {
                if ($deviceEntryAlarm->isDigitalEntryActive() === false || $deviceEntryAlarm->getEntry() !== $sensor) {
                    continue;
                }

                if (((bool)$device->getEntryData($sensor)['d_use'] === true) && $deviceEntryAlarm->isDigitalEntryAlarmValue() !== $deviceData->isD($sensor)) {
                    $this->createAlarm($deviceData, $type, $sensor);
                } else {
                    $this->closeAlarm($deviceData, $type, $sensor);
                }

                continue 2;
            }
        }
    }
}