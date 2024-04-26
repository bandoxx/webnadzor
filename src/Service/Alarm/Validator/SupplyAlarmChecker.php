<?php

namespace App\Service\Alarm\Validator;

use App\Entity\DeviceData;
use App\Service\Alarm\AlarmHandlerInterface;

class SupplyAlarmChecker extends BaseAlarmHandler implements AlarmHandlerInterface
{
    public function validate(DeviceData $deviceData): void
    {
        $this->alarmShouldBeOn = false;
        $alarm = $this->findAlarm($deviceData->getDevice(), AlarmHandlerInterface::SUPPLY_OFF);

        if (!$deviceData->isSupply()) {
            $this->alarmShouldBeOn = true;
        }

        $this->finish($alarm, $deviceData, AlarmHandlerInterface::SUPPLY_OFF);
    }

}