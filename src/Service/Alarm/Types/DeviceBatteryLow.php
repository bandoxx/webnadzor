<?php

namespace App\Service\Alarm\Types;

use App\Entity\Device;
use App\Entity\DeviceData;

class DeviceBatteryLow extends BaseType implements AlarmTypeInterface
{
    public const TYPE = 'device-battery-low';

    public function getType(): string
    {
        return self::TYPE;
    }

    public function getMessage(DeviceData $deviceData, ?int $sensor = null): string
    {
        /** @var Device $device */
        $device = $deviceData->getDevice();

        return sprintf("%s ima nizak nivo baterije %s%%. %s",
            $this->getLocationString($device, $sensor),
            $deviceData->getBattery(),
            $this->alarmActivatedString($deviceData)
        );
    }
}