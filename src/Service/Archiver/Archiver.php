<?php

namespace App\Service\Archiver;

class Archiver
{

    public function __construct(private readonly string $archiveDirectory) {}

    public function getArchiveDirectory(): string
    {
        return $this->archiveDirectory;
    }
}