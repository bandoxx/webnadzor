<?php

namespace App\Service\Alarm\Types;

use App\Entity\Device;
use App\Entity\DeviceData;

class DeviceSupplyOff extends BaseType implements AlarmTypeInterface
{
    public const string TYPE = 'device-supply-off';

    public function getType(): string
    {
        return self::TYPE;
    }

    public function getMessage(DeviceData $deviceData, ?int $sensor = null): string
    {
        /** @var Device $device */
        $device = $deviceData->getDevice();

        return sprintf("%s je ostao bez napajanja.",
            $this->getLocationString($device, $sensor),
        );
    }

    public function getShortMessage(DeviceData $deviceData, ?int $sensor = null): string
    {
        return $this->getMessage($deviceData, $sensor);
    }
}