<?php

namespace App\Service\Image;

use App\Entity\ClientStorage;
use Doctrine\ORM\EntityManagerInterface;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class ClientStorageUploader
{

    public function __construct(
        private readonly SluggerInterface      $slugger,
        private readonly EntityManagerInterface $entityManager,
        private readonly string $clientStorageDirectory
    ) {}

    public function uploadAndSave(UploadedFile $uploadedFile, ClientStorage $clientStorage): void
    {
        $originalFilename = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
        // this is needed to safely include the file name as part of the URL
        $safeFilename = $this->slugger->slug($originalFilename);
        $newFilename = sprintf("%d-%s.%s", $clientStorage->getId(), $safeFilename, $uploadedFile->guessExtension());

        // Move the file to the directory where brochures are stored
        $uploadedFile->move(
            $this->clientStorageDirectory,
            $newFilename
        );

        $this->resize(sprintf("%s/%s", $this->clientStorageDirectory, $newFilename));

        $clientStorage->setImage($newFilename);
        $this->entityManager->flush();
    }

    private function resize(string $filePath): void
    {
        [$width, $height] = getimagesize($filePath);

        $scale = min(1600 / $width, 600 / $height);

        dump($scale);

        if ($scale >= 1) {
            return;
        }

        $manager = new ImageManager(
            new Driver()
        );
        $newHeight = $height * $scale;
        $newWidth = $width * $scale;

        $image = $manager->read($filePath);
        $image->scale($newWidth, $newHeight);
        $image->save($filePath);
    }

}