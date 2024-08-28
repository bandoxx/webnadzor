<?php

namespace App\Controller\ClientStorage;
use App\Entity\ClientStorage;
use App\Service\Image\ClientStorageImageGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/image/client-storage/{id}.png', name: 'app_client_storage_image')]
class ClientStorageImageController extends AbstractController
{
    public function __invoke(
        ClientStorage $clientStorage,
        ClientStorageImageGenerator $imageGenerator
    ): StreamedResponse
    {
        $response = new StreamedResponse(
            function () use ($imageGenerator, $clientStorage) {
                $imageGenerator->generateDeviceStorage($clientStorage);
            }
        );

        $response->headers->set('Content-Type', 'image/png');
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        $response->setSharedMaxAge(60);

        return $response;
    }
}