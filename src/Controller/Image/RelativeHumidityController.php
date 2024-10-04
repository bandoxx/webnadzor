<?php

namespace App\Controller\Image;

use App\Entity\Device;
use App\Service\Image\ImageGenerator;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/image/rh/{deviceId}/{entry}.png', name: 'app_image_rh')]
class RelativeHumidityController extends AbstractController
{

    public function __invoke(
        #[MapEntity(id: 'deviceId')]
        Device $device,
        int $entry,
        ImageGenerator $imageGenerator
    ): StreamedResponse
    {
        $deviceId = $device->getId();

        $response = new StreamedResponse(
            function () use ($imageGenerator, $deviceId, $entry) {
                $imageGenerator->generateRelativeHumidity($deviceId, $entry);
            }
        );

        $response->headers->set('Content-Type', 'image/png');
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        $response->setSharedMaxAge(60);

        return $response;
    }
}