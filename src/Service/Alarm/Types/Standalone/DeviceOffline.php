<?php

namespace App\Service\Alarm\Types\Standalone;

use App\Entity\Device;
use App\Entity\DeviceData;
use App\Service\Alarm\Types\AlarmTypeInterface;
use App\Service\Alarm\Types\BaseType;

class DeviceOffline extends BaseType implements AlarmTypeInterface
{
    public const TYPE = 'device-offline';

    public function getType(): string
    {
        return self::TYPE;
    }

    public function getMessage(DeviceData $deviceData, ?int $sensor = null): string
    {
        /** @var Device $device */
        $device = $deviceData->getDevice();

        return sprintf("%s je offline.",
            $this->getLocationString($device, $sensor)
        );
    }
}