<?php

namespace App\Service\Alarm\Types;

use App\Entity\Device;
use App\Entity\DeviceData;

class DeviceMissingData extends BaseType implements AlarmTypeInterface
{
    public const string TYPE = 'device-sensor-missing-data';

    public function getType(): string
    {
        return self::TYPE;
    }

    public function getMessage(DeviceData $deviceData, ?int $sensor = null): string
    {
        /** @var Device $device */
        $device = $deviceData->getDevice();

        return sprintf("%s postoji greška na senzoru/loši podaci.",
            $this->getLocationString($device, $sensor),
        );
    }

    public function getShortMessage(DeviceData $deviceData, ?int $sensor = null): string
    {
        return $this->getMessage($deviceData, $sensor);
    }
}