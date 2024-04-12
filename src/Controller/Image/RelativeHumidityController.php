<?php

namespace App\Controller\Image;

use App\Service\Image\ImageGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/image/rh/{deviceId}/{entry}.png', name: 'app_image_rh')]
class RelativeHumidityController extends AbstractController
{

    public function __invoke($deviceId, $entry, ImageGenerator $imageGenerator): StreamedResponse
    {
        $response = new StreamedResponse(
            function () use ($imageGenerator, $deviceId, $entry) {
                $imageGenerator->generateRelativyHumidity($deviceId, $entry);
            }
        );

        $response->headers->set('Content-Type', 'image/png');
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        $response->headers->set('Expires', 'Thu, 19 Nov 1981 08:52:00 GMT');
        $response->setSharedMaxAge(60);

        return $response;
    }
}