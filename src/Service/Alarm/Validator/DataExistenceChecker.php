<?php

namespace App\Service\Alarm\Validator;

use App\Entity\ClientSetting;
use App\Entity\Device;
use App\Entity\DeviceData;
use App\Service\Alarm\AlarmHandlerInterface;

class DataExistenceChecker extends BaseAlarmHandler implements AlarmHandlerInterface
{
    public function validate(DeviceData $deviceData, ClientSetting $clientSetting): void
    {
        if (!$clientSetting->isDeviceSensorErrorAlarmActive()) {
            return;
        }

        $device = $deviceData->getDevice();
        foreach (range(1, 2) as $entry) {
            $alarm = $this->findAlarm($device, AlarmHandlerInterface::SENSOR_ERROR, $entry);

            if ($this->checkAlarm($device, $deviceData, $entry)) {
                $this->finishAlarm($alarm, $deviceData, $entry);
                break;
            }
        }
    }

    private function checkAlarm(Device $device, DeviceData $deviceData, int $entry): bool
    {
        $this->alarmShouldBeOn = false;
        return $this->checkTemperatureAlarm($device, $deviceData, $entry) || $this->checkHumidityAlarm($device, $deviceData, $entry);
    }

    private function checkTemperatureAlarm(Device $device, DeviceData $deviceData, int $entry): bool
    {
        $isTemperatureAlarm = (bool)$device->getEntryData($entry)['t_use'] === true && (!is_numeric($deviceData->getT($entry)) || $deviceData->getT($entry) == 0);

        if ($isTemperatureAlarm) {
            $this->alarmShouldBeOn = true;
        }

        return $isTemperatureAlarm;
    }

    private function checkHumidityAlarm(Device $device, DeviceData $deviceData, int $entry): bool
    {
        $isHumidityAlarm = (bool)$device->getEntryData($entry)['rh_use'] === true && (!is_numeric($deviceData->getRH($entry)) || $deviceData->getT($entry) == 0);

        if ($isHumidityAlarm) {
            $this->alarmShouldBeOn = true;
        }

        return $isHumidityAlarm;
    }

    private function finishAlarm($alarm, DeviceData $deviceData, int $entry): void
    {
        $this->finish($alarm, $deviceData, AlarmHandlerInterface::SENSOR_ERROR, $entry);
    }
}