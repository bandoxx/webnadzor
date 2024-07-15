<?php

namespace App\Controller\Image;
use App\Repository\ClientStorageDeviceRepository;
use App\Repository\ClientStorageRepository;
use App\Repository\ClientStorageTextRepository;
use App\Service\Image\ImageGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
#[Route('/image/{clientStorageId}.png', name: 'app_device_image_getimage')]

class CustomerDeviceController extends AbstractController
{
    public function __invoke(
        $clientStorageId,
        ClientStorageTextRepository $clientStorageTextRepository,
        ClientStorageRepository $clientStorageRepository,
        ClientStorageDeviceRepository $clientStorageDeviceRepository,
        ImageGenerator $imageGenerator): StreamedResponse
    {
        $clientStorageData = $clientStorageRepository->find($clientStorageId);
        $clientStorageTextRData = $clientStorageTextRepository->getByClientId($clientStorageId);
        $clientStorageDeviceData = $clientStorageDeviceRepository->getByClientId($clientStorageId);
        $mergedClientStorageDevices = array_merge($clientStorageTextRData,$clientStorageDeviceData);


        $response = new StreamedResponse(
            function () use ($imageGenerator,$clientStorageData,$mergedClientStorageDevices) {
                $imageGenerator->generateDeviceStorage($clientStorageData,$mergedClientStorageDevices);
            }
        );

        $response->headers->set('Content-Type', 'image/png');
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        $response->setSharedMaxAge(60);

        return $response;
    }
}