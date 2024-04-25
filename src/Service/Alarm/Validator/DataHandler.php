<?php

namespace App\Service\Alarm\Validator;

use App\Entity\DeviceData;
use App\Service\Alarm\AlarmHandlerInterface;

class DataHandler extends BaseAlarmHandler implements AlarmHandlerInterface
{

    public function validate(DeviceData $deviceData): void
    {
        $this->alarmShouldBeOn = false;
        $alarm = $this->findAlarm($deviceData->getDevice(), AlarmHandlerInterface::SENSOR_ERROR);

        $device = $deviceData->getDevice();

        foreach (range(1, 2) as $entry) {
            if (
                ($device->getEntryData($entry)['t_use'] xor is_numeric($deviceData->getT($entry)))
                ||
                ($device->getEntryData($entry)['rh_use'] xor is_numeric($deviceData->getRh($entry)))
            ) {
                $this->alarmShouldBeOn = true;
                break;
            }
        }

        $this->finish($alarm, $deviceData, AlarmHandlerInterface::BATTERY_LEVEL);
    }

}