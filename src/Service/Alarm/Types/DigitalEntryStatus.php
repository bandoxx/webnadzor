<?php

namespace App\Service\Alarm\Types;

use App\Entity\Device;
use App\Entity\DeviceData;

class DigitalEntryStatus extends BaseType implements AlarmTypeInterface
{
    public const string TYPE = 'digital-entry-status';

    public function getType(): string
    {
        return self::TYPE;
    }

    public function getMessage(DeviceData $deviceData, ?int $sensor = null): string
    {
        /** @var Device $device */
        $device = $deviceData->getDevice();

        return $this->getLocationStringForDigitalEntry($device, $sensor, $deviceData->isD($sensor));
    }

    public function getShortMessage(DeviceData $deviceData, ?int $sensor = null): string
    {
        return $this->getMessage($deviceData, $sensor);
    }
}