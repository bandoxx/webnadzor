<?php

namespace App\Service\Alarm\Validator;

use App\Entity\ClientSetting;
use App\Entity\Device;
use App\Entity\DeviceData;
use App\Service\Alarm\AlarmHandlerInterface;
use App\Service\Alarm\Types\DeviceMissingData;

class DataExistenceChecker extends BaseAlarmHandler implements AlarmHandlerInterface
{
    public function validate(DeviceData $deviceData, ClientSetting $clientSetting): void
    {
        if (!$clientSetting->isDeviceSensorErrorAlarmActive()) {
            return;
        }

        $type = new DeviceMissingData();

        foreach (range(1, 2) as $entry) {
            if ($this->doesDeviceHasDataError($deviceData, $entry)) {
                $this->createAlarm($deviceData, $type, $entry);
            } else {
                $this->closeAlarm($deviceData, $type, $entry);
            }
        }
    }

    private function doesDeviceHasDataError(DeviceData $deviceData, int $entry): bool
    {
        /** @var Device $device */
        $device = $deviceData->getDevice();

        return $this->isTemperatureDataWrong($device, $deviceData, $entry) || $this->isHumidityDataWrong($device, $deviceData, $entry);
    }

    private function isTemperatureDataWrong(Device $device, DeviceData $deviceData, int $entry): bool
    {
        return (bool) $device->getEntryData($entry)['t_use'] === true && (!is_numeric($deviceData->getT($entry)) || $deviceData->getT($entry) == 0);
    }

    private function isHumidityDataWrong(Device $device, DeviceData $deviceData, int $entry): bool
    {
        return (bool)$device->getEntryData($entry)['rh_use'] === true && (!is_numeric($deviceData->getRH($entry)) || $deviceData->getT($entry) == 0);
    }
}