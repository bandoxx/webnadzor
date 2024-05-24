<?php

namespace App\Service\Alarm\Types;

use App\Entity\Device;
use App\Entity\DeviceData;

class DeviceSupplyOff extends BaseType implements AlarmTypeInterface
{
    public const TYPE = 'device-supply-off';

    public function getType(): string
    {
        return self::TYPE;
    }

    public function getMessage(DeviceData $deviceData, ?int $sensor = null): string
    {
        /** @var Device $device */
        $device = $deviceData->getDevice();

        return sprintf("%s je ostao bez napajanja. %s",
            $this->getLocationString($device, $sensor),
            $this->alarmActivatedString($deviceData)
        );
    }
}