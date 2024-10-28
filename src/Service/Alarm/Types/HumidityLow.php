<?php

namespace App\Service\Alarm\Types;

use App\Entity\Device;
use App\Entity\DeviceData;

class HumidityLow extends BaseType implements AlarmTypeInterface
{
    public const string TYPE = 'humidity-low';

    public function getType(): string
    {
        return self::TYPE;
    }

    public function getMessage(DeviceData $deviceData, ?int $sensor = null): string
    {
        /** @var Device $device */
        $device = $deviceData->getDevice();

        return sprintf("%s ima nisku vlagu: %s, minimalno dozvoljeno: %s.",
            $this->getLocationString($device, $sensor),
            $deviceData->getRh($sensor),
            $device->getEntryData($sensor)['rh_min']
        );
    }

    public function getShortMessage(DeviceData $deviceData, ?int $sensor = null): string
    {
        /** @var Device $device */
        $device = $deviceData->getDevice();

        return sprintf("%s ima nisku vlagu: %s.",
            $this->getLocationString($device, $sensor),
            $deviceData->getRh($sensor),
        );
    }
}