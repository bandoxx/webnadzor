<?php

namespace App\Service\Alarm;

use App\Service\Alarm\Types\DeviceSupplyOff;
use App\Service\Alarm\Types\HumidityHigh;
use App\Service\Alarm\Types\HumidityLow;
use App\Service\Alarm\Types\Standalone\DeviceOffline;
use App\Service\Alarm\Types\TemperatureHigh;
use App\Service\Alarm\Types\TemperatureLow;

/**
 * Centralized alarm type groupings used across the alarm system.
 * Single source of truth for categorizing alarm types.
 */
final class AlarmTypeGroups
{
    /** Device-level alarms (not sensor-specific) */
    public const array DEVICE_LEVEL = [
        DeviceSupplyOff::TYPE,
        DeviceOffline::TYPE,
    ];

    /** Temperature-related alarms */
    public const array TEMPERATURE = [
        TemperatureHigh::TYPE,
        TemperatureLow::TYPE,
    ];

    /** Humidity-related alarms */
    public const array HUMIDITY = [
        HumidityHigh::TYPE,
        HumidityLow::TYPE,
    ];

    public static function isDeviceLevelAlarm(string $type): bool
    {
        return in_array($type, self::DEVICE_LEVEL, true);
    }

    public static function isTemperatureAlarm(string $type): bool
    {
        return in_array($type, self::TEMPERATURE, true);
    }

    public static function isHumidityAlarm(string $type): bool
    {
        return in_array($type, self::HUMIDITY, true);
    }
}
