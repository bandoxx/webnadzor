<?php

namespace App\Service\Archiver;

use App\Factory\DeviceSimListFactory;
use App\Service\Archiver\Model\ArchiveModel;

class Archiver
{

    public function __construct(
        private readonly string $archiveDirectory,
        private DeviceSimListFactory $deviceSimListFactory
    ) {}

    public function getDeviceSimListFactory(): DeviceSimListFactory
    {
        return $this->deviceSimListFactory;
    }

    public function getArchiveDirectory(): string
    {
        return $this->archiveDirectory;
    }

    public function createArchiveModel(string $filePath, string $fileName): ArchiveModel
    {
        return (new ArchiveModel($filePath, $fileName));
    }
}