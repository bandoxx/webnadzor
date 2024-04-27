<?php

namespace App\Service\XmlParser;

class ParserTemperatureChecker
{
    public static function temperature(string $value): ?string
    {
        if (preg_match('/^-?\d{1,2}(\.\d{1,2})?$/', $value)) {
            return number_format($value, 2);
        }

        return null;
    }

    public static function relativeHumidity(string $value): ?string
    {
        if (preg_match('/^\d{1,3}(\.\d{1,2})?$/', $value)) {
            return number_format($value, 2);
        }

        return null;
    }
}