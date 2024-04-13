<?php

namespace App\Service\Image;

use App\Entity\Client;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class LogoUploader
{

    public function __construct(private SluggerInterface $slugger, private ParameterBagInterface $parameterBag, private EntityManagerInterface $entityManager) {}

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

        $client->setMainLogo($newFilename);

        $this->entityManager->flush();
    }

    private function save(UploadedFile $uploadedFile, $fileName)
    {
        try {
            $uploadedFile->move(
                $this->parameterBag->get('logo_directory_full_path'),
                $fileName
            );
        } catch (FileException $e) {
            return;
        }
    }

}