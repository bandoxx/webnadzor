<?php

namespace App\Service\Image\ClientImage;

use App\Entity\ClientStorage;
use App\Service\FileUploader\DTO\FileUploaderResponse;
use App\Service\FileUploader\Types\ClientStorageImageUploader;
use App\Service\Image\ImageResizer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ScadaImageHandler
{
    public const IMAGE_WIDTH = 1200;
    public const IMAGE_HEIGHT = 700;

    public function __construct(
        private readonly ImageResizer $imageResizer,
        private readonly EntityManagerInterface $entityManager,
        private readonly ClientStorageImageUploader $clientStorageImageUploader
    )
    {
    }

    public function upload(UploadedFile $file, ClientStorage $clientStorage): string
    {
        $upload = $this->clientStorageImageUploader->upload($file, $clientStorage->getId());

        $this->resize($upload);

        return $upload->getFileName();
    }

    public function save(ClientStorage $clientStorage, string $fileName): void
    {
        $clientStorage->setImage($fileName);
        $this->entityManager->flush();
    }

    private function resize(FileUploaderResponse $upload): void
    {
        [$width, $height] = getimagesize($upload->getFullPath());

        $scale = min(self::IMAGE_WIDTH / $width, self::IMAGE_HEIGHT / $height);

        if ($scale < 1) {
            $this->imageResizer::resize($upload->getFileName(), $width * $scale, $height * $scale);
        }
    }
}