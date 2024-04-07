<?php

namespace App\Controller;

use App\Repository\DeviceDataRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;

class ImageController extends AbstractController
{

    #[Route('/image/{deviceId}/{entry}.png', name: 'app_image_getimage')]
    public function getImage($deviceId, $entry, DeviceDataRepository $deviceDataRepository)
    {
        $data = $deviceDataRepository->findLastRecordForDeviceId($deviceId, $entry);

        $temperature = $data?->getT($entry);

        $response = new StreamedResponse(
            function () use ($temperature) {
                $im = @ImageCreateFromPNG(__DIR__ . '/../../public/assets/images/termostat.png');
                imagealphablending($im, false);
                imagesavealpha($im, true);
                $blue = ImageColorAllocate($im, 50, 110, 176);
                $red = ImageColorAllocate($im, 255, 0, 0);


                $rx = 74;
                $ry = 246;
                $rWidth = 4;

                if (!is_null($temperature)) {
                    $r_height = 238-196/90*(40 + $temperature);
                    $fill_color = $blue;
                } else {
                    $r_height = 42;
                    imagestring($im, 3, 52, 18, 'Greska!', $red);
                    $fill_color = $red;
                }

                ImageFilledRectangle($im, $rx, $ry, $rx+$rWidth, $r_height, $fill_color);

                ImagePNG($im);
                imagedestroy($im);
            }
        );

        $response->headers->set('Content-Type', 'image/png');
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        $response->headers->set('Expires', 'Thu, 19 Nov 1981 08:52:00 GMT');
        $response->setSharedMaxAge(60);

        return $response;
    }

}