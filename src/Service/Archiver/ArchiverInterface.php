<?php

namespace App\Service\Archiver;

use App\Entity\Device;

interface ArchiverInterface
{

    public const DAILY_FORMAT = 'd.m.Y.';
    public const DAILY_FILENAME_FORMAT = 'd-m-Y';
    public const MONTHLY_FORMAT = 'm.Y.';
    public const MONTHLY_FILENAME_FORMAT = 'm-Y';

    public function saveDaily(Device $device, array $deviceData, $entry, \DateTime $archiveDate, string $fileName);
    public function saveMonthly(Device $device, array $deviceData, $entry, \DateTime $archiveDate, string $fileName);
    public function saveCustom(Device $device, array $deviceData, $entry, \DateTime $fromDate, \DateTime $toDate, string $fileName);
}