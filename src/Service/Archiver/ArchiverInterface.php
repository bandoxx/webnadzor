<?php

namespace App\Service\Archiver;

interface ArchiverInterface
{
    public const DAILY_FORMAT = 'd.m.Y.';
    public const DAILY_FILENAME_FORMAT = 'd-m-Y';
    public const MONTHLY_FORMAT = 'm.Y.';
    public const MONTHLY_FILENAME_FORMAT = 'm-Y';
}