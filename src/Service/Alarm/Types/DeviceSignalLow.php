<?php

namespace App\Service\Alarm\Types;

use App\Entity\Device;
use App\Entity\DeviceData;

class DeviceSignalLow extends BaseType implements AlarmTypeInterface
{
    public const string TYPE = 'device-signal-low';

    public function getType(): string
    {
        return self::TYPE;
    }

    public function getMessage(DeviceData $deviceData, ?int $sensor = null): string
    {
        /** @var Device $device */
        $device = $deviceData->getDevice();

        return sprintf("%s ima nizak signal: %s%%. %s",
            $this->getLocationString($device, $sensor),
            $deviceData->getGsmSignal(),
            $this->alarmActivatedString($deviceData)
        );
    }
}