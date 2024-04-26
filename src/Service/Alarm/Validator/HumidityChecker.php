<?php

namespace App\Service\Alarm\Validator;

use App\Entity\DeviceData;
use App\Service\Alarm\AlarmHandlerInterface;

class HumidityChecker extends BaseAlarmHandler implements AlarmHandlerInterface
{
    public function validate(DeviceData $deviceData): void
    {
        foreach (range(1, 2) as $entry) {
            $this->alarmShouldBeOn = false;

            $alarm = $this->findAlarm($deviceData->getDevice(), AlarmHandlerInterface::HUMIDITY_OFFSET, $entry);

            if ($deviceData->isHumidityOutOfRange($entry)) {
                $this->alarmShouldBeOn = true;
            }

            $this->finish($alarm, $deviceData, AlarmHandlerInterface::HUMIDITY_OFFSET, $entry);
        }
    }
}