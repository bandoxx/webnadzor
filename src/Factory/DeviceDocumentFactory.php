<?php

namespace App\Factory;

use App\Entity\Device;
use App\Entity\DeviceDocument;

class DeviceDocumentFactory
{
    public function create(Device $device, int $entry, string $fileName, int $year, string $documentNumber, string $serialNumber): DeviceDocument
    {
        return (new DeviceDocument())
            ->setDevice($device)
            ->setEntry($entry)
            ->setFile($fileName)
            ->setYear($year)
            ->setNumberOfDocument($documentNumber)
            ->setSerialSensorNumber($serialNumber)
        ;
    }
}