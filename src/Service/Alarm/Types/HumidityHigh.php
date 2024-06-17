<?php

namespace App\Service\Alarm\Types;

use App\Entity\Device;
use App\Entity\DeviceData;

class HumidityHigh extends BaseType implements AlarmTypeInterface
{
    public const TYPE = 'humidity-high';

    public function getType(): string
    {
        return self::TYPE;
    }

    public function getMessage(DeviceData $deviceData, ?int $sensor = null): string
    {
        /** @var Device $device */
        $device = $deviceData->getDevice();

        return sprintf("%s ima visoku vlagu: %s, maksimalno dozvoljeno: %s. %s",
            $this->getLocationString($device, $sensor),
            $deviceData->getRh($sensor),
            $device->getEntryData($sensor)['rh_max'],
            $this->alarmActivatedString($deviceData)
        );
    }
}