<?php

namespace App\Service\Alarm\Validator;

use App\Entity\ClientSetting;
use App\Entity\Device;
use App\Entity\DeviceData;
use App\Service\Alarm\AlarmHandlerInterface;
use App\Service\Alarm\Types\TemperatureHigh;

class TemperatureHighChecker extends BaseAlarmHandler implements AlarmHandlerInterface
{
    public function validate(DeviceData $deviceData, ClientSetting $clientSetting): void
    {
        /** @var Device $device */
        $device = $deviceData->getDevice();
        $type = new TemperatureHigh();

        if (!$clientSetting->getIsTemperatureAlarmActive()) {
            $this->closeAlarm($deviceData, $type);
            return;
        }

        foreach (range(1, 2) as $sensor) {
            if (((bool)$device->getEntryData($sensor)['t_use'] === true) && $deviceData->isTemperatureHigh($sensor)) {
                $this->createAlarm($deviceData, $type, $sensor);
            } else {
                $this->closeAlarm($deviceData, $type, $sensor);
            }
        }
    }
}