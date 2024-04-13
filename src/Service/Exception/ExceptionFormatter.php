<?php

namespace App\Service\Exception;

class ExceptionFormatter
{

    public static function string(\Throwable $exception): string
    {
        return sprintf("Error: %s\nLine: %s\nCode: %s\nFile: %s\n", $exception->getMessage(), $exception->getLine(), $exception->getCode(), $exception->getFile());
    }

}