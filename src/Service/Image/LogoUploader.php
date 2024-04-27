<?php

namespace App\Service\Image;

use App\Entity\Client;
use Doctrine\ORM\EntityManagerInterface;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class LogoUploader
{

    public const LOGO_HEIGHT = 65;
    public const LOGO_WIDTH = 180;
    public const ICON_HEIGHT = 32;
    public const ICON_WIDTH = 32;

    public function __construct(
        private readonly SluggerInterface       $slugger,
        private readonly EntityManagerInterface $entityManager,
        private readonly string                 $logoPath,
        private readonly string $mapMarkerPath
    ) {}

    public function uploadAndSaveMainLogo(UploadedFile $uploadedFile, Client $client): void
    {
        $fileName = $this->slugger->slug(sprintf("%s-main-logo", $client->getId()));
        $newFilename = $this->save($uploadedFile, $this->logoPath, $fileName, self::LOGO_WIDTH, self::LOGO_HEIGHT);

        if (!$newFilename) {
            return;
        }

        $client->setMainLogo($newFilename);

        $this->entityManager->flush();
    }

    public function uploadAndSavePDFLogo(UploadedFile $uploadedFile, Client $client): void
    {
        $fileName = $this->slugger->slug(sprintf("%s-pdf-logo", $client->getId()));
        $newFilename = $this->save($uploadedFile, $this->logoPath, $fileName, self::LOGO_WIDTH, self::LOGO_HEIGHT);

        if (!$newFilename) {
            return;
        }

        $client->setPdfLogo($newFilename);
        $this->entityManager->flush();
    }

    public function uploadAndSaveMapMarkerIcon(UploadedFile $uploadedFile, Client $client): void
    {
        $fileName = $this->slugger->slug(sprintf("%s-map-marker", $client->getId()));

        $newFilename = $this->save($uploadedFile, $this->mapMarkerPath, $fileName, self::ICON_WIDTH, self::ICON_HEIGHT);

        if (!$newFilename) {
            return;
        }

        $client->setMapMarkerIcon($newFilename);
        $this->entityManager->flush();
    }

    private function save(UploadedFile $uploadedFile, string $path, string $fileName, int $width, int $height): string
    {
        $newFileName = sprintf("%s.%s", $fileName, $uploadedFile->guessExtension());
        $uploadedFile->move($path, $newFileName);
        $this->resize(sprintf("%s/%s", $path, $newFileName), $width, $height);

        return $newFileName;
    }

    private function resize(string $filePath, int $width, int $height): void
    {
        $manager = new ImageManager(
            new Driver()
        );

        $image = $manager->read($filePath);
        $image->scale($width, $height);
        $image->save($filePath);
    }
}