<?php

namespace App\Service\Alarm\Validator;

use App\Entity\Device;
use App\Entity\DeviceData;
use App\Service\Alarm\AlarmHandlerInterface;

class DataExistenceChecker extends BaseAlarmHandler implements AlarmHandlerInterface
{
    public function validate(DeviceData $deviceData): void
    {
        /** @var Device $device */
        $device = $deviceData->getDevice();

        foreach (range(1, 2) as $entry) {
            $this->alarmShouldBeOn = false;

            $alarm = $this->findAlarm($device, AlarmHandlerInterface::SENSOR_ERROR, $entry);

            if ((bool) $device->getEntryData($entry)['t_use'] === true && !is_numeric($deviceData->getT($entry))) {
                $this->alarmShouldBeOn = true;

                $this->finish($alarm, $deviceData, AlarmHandlerInterface::SENSOR_ERROR, $entry);
                break;
            }

            if ((bool) $device->getEntryData($entry)['rh_use'] === true && !is_numeric($deviceData->getRH($entry))) {
                $this->alarmShouldBeOn = true;

                $this->finish($alarm, $deviceData, AlarmHandlerInterface::SENSOR_ERROR, $entry);
                break;
            }

            $this->finish($alarm, $deviceData, AlarmHandlerInterface::SENSOR_ERROR, $entry);
        }

    }
}