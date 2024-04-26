<?php

namespace App\Service\Archiver\DeviceData;

use App\Entity\Device;
use App\Service\Archiver\ArchiverInterface;

interface DeviceDataArchiverInterface extends ArchiverInterface
{
    public function saveDaily(Device $device, array $deviceData, $entry, \DateTime $archiveDate, string $fileName);
    public function saveMonthly(Device $device, array $deviceData, $entry, \DateTime $archiveDate, string $fileName);
    public function saveCustom(Device $device, array $deviceData, $entry, \DateTime $fromDate, \DateTime $toDate, string $fileName);
}