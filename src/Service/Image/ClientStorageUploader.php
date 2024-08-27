<?php

namespace App\Service\Image;

use App\Entity\ClientStorage;
use Doctrine\ORM\EntityManagerInterface;
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

        dump($this->clientStorageDirectory);
        // Move the file to the directory where brochures are stored
        $uploadedFile->move(
            $this->clientStorageDirectory,
            $newFilename
        );

        $clientStorage->setImage($newFilename);
        $this->entityManager->flush();
    }

}