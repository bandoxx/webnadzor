<?php

namespace App\Model;

class AlarmType
{

    public const DEVICE_OFFLINE = 'device-offline';
    public const SUPPLY_OFF = 'power-off';
    public const NO_DATA = 'no-data';
    public const BATTERY_LEVEL = 'battery-level';
    public const SIGNAL_LEVEL = 'signal-level';
    public const SENSOR_ERROR = 'sensor-error';

}