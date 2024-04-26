<?php

namespace App\Service\Alarm\Validator;

use App\Entity\DeviceData;
use App\Service\Alarm\AlarmHandlerInterface;

class TemperatureChecker extends BaseAlarmHandler implements AlarmHandlerInterface
{
    public function validate(DeviceData $deviceData): void
    {
        $device = $deviceData->getDevice();

        foreach (range(1, 2) as $entry) {
            $this->alarmShouldBeOn = false;

            $alarm = $this->findAlarm($device, AlarmHandlerInterface::TEMPERATURE_OFFSET, $entry);

            if ((bool) $device->getEntryData($entry)['t_use'] === true && $deviceData->isTemperatureOutOfRange($entry)) {
                $this->alarmShouldBeOn = true;
            }

            $this->finish($alarm, $deviceData, AlarmHandlerInterface::TEMPERATURE_OFFSET, $entry);
        }
    }
}