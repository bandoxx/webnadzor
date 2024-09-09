<?php

namespace App\Service\FileUploader;

use App\Service\FileUploader\DTO\FileUploaderResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class BaseUploader implements FileUploaderInterface
{

    public function __construct(
        private SluggerInterface $slugger,
        private string $targetDirectory
    ) {}

    public function upload(UploadedFile $file, ?string $fileName = null): FileUploaderResponse
    {
        if (empty($fileName)) {
            $fileName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        }

        // this is needed to safely include the file name as part of the URL
        $safeFilename = $this->slugger->slug($fileName);
        $newFilename = sprintf("%s-%s.%s", $safeFilename, uniqid(), $file->guessExtension());

        // Move the file to the directory where brochures are stored
        $file->move(
            $this->targetDirectory,
            $newFilename
        );

        return new FileUploaderResponse($this->targetDirectory, $newFilename);
    }

}