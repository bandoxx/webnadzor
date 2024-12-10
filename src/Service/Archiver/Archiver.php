<?php

namespace App\Service\Archiver;

use App\Service\Archiver\Model\ArchiveModel;

class Archiver
{

    public function __construct(
        private readonly string $archiveDirectory,
        private readonly string $projectDirectory
    ) {}

    public function getProjectDirectory(): string
    {
        return $this->projectDirectory;
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