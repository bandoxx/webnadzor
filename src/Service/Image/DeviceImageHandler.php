<?php

namespace App\Service\Image;

use App\Factory\DeviceIconFactory;
use App\Service\FileUploader\Types\ImageUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class DeviceImageHandler
{

    public function __construct(
        private readonly ImageUploader $imageUploader,
        private readonly DeviceIconFactory $deviceIconFactory,
        private readonly EntityManagerInterface $entityManager
    ) {}

    public function upload(UploadedFile $uploadedFile): string
    {
        return $this->imageUploader->upload($uploadedFile)->getFileName();
    }

    public function save(string $fileName, string $title): void
    {
        $deviceIcon = $this->deviceIconFactory->create($fileName, $title);

        $this->entityManager->persist($deviceIcon);
        $this->entityManager->flush();
    }
}