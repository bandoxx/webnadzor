<?php

namespace App\Service\Alarm\Validator;

use App\Entity\DeviceData;
use App\Service\Alarm\AlarmHandlerInterface;

class TemperatureChecker extends BaseAlarmHandler implements AlarmHandlerInterface
{
    public function validate(DeviceData $deviceData): void
    {
        $this->alarmShouldBeOn = false;
        $alarm = $this->findAlarm($deviceData->getDevice(), AlarmHandlerInterface::TEMPERATURE_OFFSET);

        $device = $deviceData->getDevice();

        foreach (range(1, 2) as $entry) {
            if (!$device->getEntryData($entry)['t_use']) {
                return;
            }

            $max = $device->getEntryData($entry)['t_max'];
            $min = $device->getEntryData($entry)['t_min'];

            $t = $deviceData->getT($entry);

            if (is_numeric($min)) {
                if (!$t || $t < $min) {
                    $this->alarmShouldBeOn = true;
                    break;
                }

            }

            if (is_numeric($max)) {
                if (!$t || $t > $max) {
                    $this->alarmShouldBeOn = true;
                    break;
                }
            }
        }

        $this->finish($alarm, $deviceData, AlarmHandlerInterface::TEMPERATURE_OFFSET);
    }

}