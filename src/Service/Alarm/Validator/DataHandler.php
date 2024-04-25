<?php

namespace App\Service\Alarm\Validator;

use App\Entity\DeviceData;
use App\Service\Alarm\AlarmHandlerInterface;

class DataHandler extends BaseAlarmHandler implements AlarmHandlerInterface
{

    public function validate(DeviceData $deviceData): void
    {
        $this->alarmShouldBeOn = false;
        $alarm = $this->findAlarm($deviceData->getDevice(), AlarmHandlerInterface::NO_DATA);

        $device = $deviceData->getDevice();

        foreach (range(1, 2) as $entry) {
            if ($device->getEntryData($entry)['t_use'] == true && !is_numeric($deviceData->getT($entry))) {
                $this->alarmShouldBeOn = true;
            }

            if ($device->getEntryData($entry)['rh_use'] == true && !is_numeric($deviceData->getRH($entry))) {
                $this->alarmShouldBeOn = true;
            }

            if ($this->alarmShouldBeOn) {
                break;
            }
        }

        $this->finish($alarm, $deviceData, AlarmHandlerInterface::NO_DATA);
    }
}