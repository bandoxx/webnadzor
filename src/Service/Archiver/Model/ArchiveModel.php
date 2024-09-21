<?php

namespace App\Service\Archiver\Model;

class ArchiveModel
{

    public function __construct(
        private readonly string $filePath,
        private readonly string $fileName,
    ) {}

    public function getFilePath(): string
    {
        return $this->filePath;
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }

    public function getFullPath(): string
    {
        return $this->filePath . '/' . $this->fileName;
    }

    public function getFullPathWithoutExtension(): string
    {
        $fileInfo = pathinfo($this->getFullPath());
        return $fileInfo['dirname'] . '/' . $fileInfo['filename'];
    }
}