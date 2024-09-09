<?php

namespace App\Service\Image\ClientImage;

use App\Entity\Client;
use App\Service\FileUploader\Types\MapMarkerIconUploader;
use App\Service\Image\ImageResizer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class MapMarkerIconHandler
{

    public const IMAGE_HEIGHT = 32;
    public const IMAGE_WIDTH = 32;

    public function __construct(
        private readonly ImageResizer $imageResizer,
        private readonly EntityManagerInterface $entityManager,
        private readonly MapMarkerIconUploader $mapMarkerIconUploader
    )
    {
    }

    public function upload(UploadedFile $file, Client $client): string
    {
        $upload = $this->mapMarkerIconUploader->upload($file, sprintf("%s-map-marker", $client->getId()));
        $this->imageResizer::resize($upload->getFullPath(), self::IMAGE_WIDTH, self::IMAGE_HEIGHT);

        return $upload->getFileName();
    }

    public function save(Client $client, string $fileName): void
    {
        $client->setMapMarkerIcon($fileName);

        $this->entityManager->flush();
    }

}