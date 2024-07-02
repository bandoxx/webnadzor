<?php

namespace App\Factory;

use App\Entity\Device;
use App\Entity\DeviceDataArchive;

class DeviceDataArchiveFactory
{

    public function create(Device $device, \DateTime $serverDate, int $entry, string $fileName, string $period): DeviceDataArchive
    {
        $archive = new DeviceDataArchive();
        $serverDate = (clone $serverDate)->modify('+1 day')->setTime(0, 15);

        $archive->setDevice($device)
            ->setServerDate(new \DateTime())
            ->setEntry($entry)
            ->setFilename($fileName)
            ->setPeriod($period)
            ->setArchiveDate($serverDate)
        ;

        return $archive;
    }

}