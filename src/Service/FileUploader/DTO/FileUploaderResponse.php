<?php

namespace App\Service\FileUploader\DTO;

class FileUploaderResponse
{
    public function __construct(
        private readonly string $directoryPath,
        private readonly string $fileName
    ) {}

    public function getDirectoryPath(): string
    {
        return $this->directoryPath;
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }

    public function getFullPath(): string
    {
        return $this->directoryPath . '/' . $this->fileName;
    }
}