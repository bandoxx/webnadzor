<?php

namespace App\Service\Alarm\Types;

use App\Entity\Device;
use App\Entity\DeviceData;

class TemperatureLow extends BaseType implements AlarmTypeInterface
{
    public const string TYPE = 'temperature-low';

    public function getType(): string
    {
        return self::TYPE;
    }

    public function getMessage(DeviceData $deviceData, ?int $sensor = null): string
    {
        /** @var Device $device */
        $device = $deviceData->getDevice();

        return sprintf("%s ima nisku temperaturu: %s, minimalno dozvoljeno: %s. %s",
            $this->getLocationString($device, $sensor),
            $deviceData->getT($sensor),
            $device->getEntryData($sensor)['t_min'],
            $this->alarmActivatedString($deviceData)
        );
    }
}