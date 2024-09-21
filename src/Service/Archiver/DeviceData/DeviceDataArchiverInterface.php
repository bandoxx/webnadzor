<?php

namespace App\Service\Archiver\DeviceData;

use App\Entity\Device;
use App\Service\Archiver\ArchiverInterface;
use App\Service\Archiver\Model\ArchiveModel;

interface DeviceDataArchiverInterface extends ArchiverInterface
{
    public function saveDaily(Device $device, array $deviceData, int $entry, \DateTime $archiveDate, ?string $fileName): ArchiveModel;
    public function saveMonthly(Device $device, array $deviceData, int $entry, \DateTime $archiveDate, ?string $fileName): ArchiveModel;
    public function saveCustom(Device $device, array $deviceData, int $entry, \DateTime $fromDate, \DateTime $toDate, ?string $fileName);
}