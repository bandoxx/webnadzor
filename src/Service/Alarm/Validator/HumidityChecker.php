<?php

namespace App\Service\Alarm\Validator;

use App\Entity\DeviceData;
use App\Service\Alarm\AlarmHandlerInterface;

class HumidityChecker extends BaseAlarmHandler implements AlarmHandlerInterface
{
    public function validate(DeviceData $deviceData): void
    {
        $this->alarmShouldBeOn = false;
        $sensor = null;

        $alarm = $this->findAlarm($deviceData->getDevice(), AlarmHandlerInterface::HUMIDITY_OFFSET);
        $device = $deviceData->getDevice();

        foreach (range(1, 2) as $entry) {
            if (!$device->getEntryData($entry)['rh_use']) {
                return;
            }

            $max = $device->getEntryData($entry)['rh_max'];
            $min = $device->getEntryData($entry)['rh_min'];

            $rh = $deviceData->getRh($entry);

            if (is_numeric($min)) {
                if (!$rh || $rh < $min) {
                    $this->alarmShouldBeOn = true;
                    $sensor = $entry;
                    break;
                }

            }

            if (is_numeric($max)) {
                if (!$rh || $rh > $max) {
                    $this->alarmShouldBeOn = true;
                    $sensor = $entry;
                    break;
                }
            }
        }

        $this->finish($alarm, $deviceData, AlarmHandlerInterface::HUMIDITY_OFFSET, $sensor);
    }

}