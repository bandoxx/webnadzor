<?php

namespace App\Model;

class TemperatureType
{

    public const CELSIUS = '°C';
    public const FAHRENHEIT = '°F';

    public static function getTypes(): array
    {
        return [
            self::CELSIUS => self::CELSIUS,
            self::FAHRENHEIT => self::FAHRENHEIT
        ];
    }

}