<?php

namespace App\Service\FileUploader;

use App\Service\FileUploader\DTO\FileUploaderResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;

interface FileUploaderInterface
{
    public function upload(UploadedFile $file, ?string $fileName = null): FileUploaderResponse;
}