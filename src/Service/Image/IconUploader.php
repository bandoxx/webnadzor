<?php

namespace App\Service\Image;

use App\Entity\Client;
use App\Entity\DeviceIcon;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class IconUploader
{

    public function __construct(private SluggerInterface $slugger, private ParameterBagInterface $parameterBag, private EntityManagerInterface $entityManager) {}

    public function uploadAndSave(UploadedFile $uploadedFile, Client $client, $title): void
    {
        $originalFilename = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
        // this is needed to safely include the file name as part of the URL
        $safeFilename = $this->slugger->slug($originalFilename);
        $newFilename = sprintf("%s-%s.%s", $safeFilename, uniqid(), $uploadedFile->guessExtension());

        // Move the file to the directory where brochures are stored
        try {
            $uploadedFile->move(
                $this->parameterBag->get('icon_directory_full_path'),
                $newFilename
            );
        } catch (FileException $e) {
            // TODO: fix this exception case
            return;
        }

        $newIcon = new DeviceIcon();
        $newIcon->setClient($client)
            ->setTitle($title)
            ->setFilename($newFilename);

        $this->entityManager->persist($newIcon);
        $this->entityManager->flush();
    }

}