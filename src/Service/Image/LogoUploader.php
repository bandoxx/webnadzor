<?php

namespace App\Service\Image;

use App\Entity\Client;
use BenMajor\ImageResize\Image;
use Doctrine\ORM\EntityManagerInterface;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class LogoUploader
{

    public function __construct(private SluggerInterface $slugger, private string $logoPath, private EntityManagerInterface $entityManager) {}

    public function uploadAndSaveMainLogo(UploadedFile $uploadedFile, Client $client): void
    {
        $safeFilename = $this->slugger->slug(sprintf("%s-main-logo", $client->getId()));
        $newFilename = sprintf("%s.%s", $safeFilename, $uploadedFile->guessExtension());

        $this->save($uploadedFile, $newFilename);

        $client->setMainLogo($newFilename);

        $this->entityManager->flush();
    }

    public function uploadAndSavePDFLogo(UploadedFile $uploadedFile, Client $client): void
    {
        $safeFilename = $this->slugger->slug(sprintf("%s-pdf-logo", $client->getId()));
        $newFilename = sprintf("%s.%s", $safeFilename, $uploadedFile->guessExtension());

        $this->save($uploadedFile, $newFilename);

        $client->setPdfLogo($newFilename);

        $this->entityManager->flush();
    }

    private function save(UploadedFile $uploadedFile, $fileName): void
    {
        try {
            $uploadedFile->move($this->logoPath, $fileName);
            $this->resize(sprintf("%s/%s", $this->logoPath, $fileName));
        } catch (FileException $e) {
            return;
        }
    }

    private function resize($filePath): void
    {
        $manager = new ImageManager(
            new Driver()
        );

        $image = $manager->read($filePath);
        $image->scale(180, 65);
        $image->save($filePath);
    }

}