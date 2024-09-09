<?php

namespace App\Service\Image\ClientImage;

use App\Entity\Client;
use App\Service\FileUploader\Types\LogoUploader;
use App\Service\Image\ImageResizer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class PdfLogoHandler
{

    public const IMAGE_HEIGHT = 58;
    public const IMAGE_WIDTH = 180;

    public function __construct(
        private readonly ImageResizer $imageResizer,
        private readonly EntityManagerInterface $entityManager,
        private readonly LogoUploader $logoUploader
    )
    {
    }

    public function upload(UploadedFile $file, Client $client): string
    {
        $upload = $this->logoUploader->upload($file, sprintf("%s-pdf-logo", $client->getId()));
        $this->imageResizer::resize($upload->getFullPath(), self::IMAGE_WIDTH, self::IMAGE_HEIGHT);

        return $upload->getFileName();
    }

    public function save(Client $client, string $fileName): void
    {
        $client->setPdfLogo($fileName);

        $this->entityManager->flush();
    }
}