<?php

namespace App\Service\Alarm;

use App\Entity\DeviceData;

interface AlarmHandlerInterface
{
    public const DEVICE_OFFLINE = 'device-offline';
    public const SUPPLY_OFF = 'supply-off';
    public const NO_DATA = 'no-data-on-sensor';
    public const BATTERY_LEVEL = 'battery-level';
    public const SIGNAL_LEVEL = 'signal-level';
    public const SENSOR_ERROR = 'sensor-error';

    public function validate(DeviceData $deviceData);


}