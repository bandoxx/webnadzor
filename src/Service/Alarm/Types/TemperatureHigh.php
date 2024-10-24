<?php

namespace App\Service\Alarm\Types;

use App\Entity\Device;
use App\Entity\DeviceData;

class TemperatureHigh extends BaseType implements AlarmTypeInterface
{
    public const string TYPE = 'temperature-high';

    public function getType(): string
    {
        return self::TYPE;
    }

    public function getMessage(DeviceData $deviceData, ?int $sensor = null): string
    {
        /** @var Device $device */
        $device = $deviceData->getDevice();

        return sprintf("%s ima visoku temperaturu: %s, maksimalno dozvoljeno: %s.",
            $this->getLocationString($device, $sensor),
            $deviceData->getT($sensor),
            $device->getEntryData($sensor)['t_max']
        );
    }
}