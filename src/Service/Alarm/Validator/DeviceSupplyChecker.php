<?php

namespace App\Service\Alarm\Validator;

use App\Entity\ClientSetting;
use App\Entity\DeviceData;
use App\Service\Alarm\AlarmHandlerInterface;
use App\Service\Alarm\Types\DeviceSupplyOff;

class DeviceSupplyChecker extends BaseAlarmHandler implements AlarmHandlerInterface
{
    public function validate(DeviceData $deviceData, ClientSetting $clientSetting): void
    {
        $type = new DeviceSupplyOff();

        if ($deviceData->isSupply()) {
            $this->closeAlarm($deviceData, $type);
        } else {
            $this->createAlarm($deviceData, $type);
        }
    }
}