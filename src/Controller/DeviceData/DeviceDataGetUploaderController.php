<?php

namespace App\Controller\DeviceData;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DeviceDataGetUploaderController extends AbstractController
{

    #[Route('/api/xml-uploader', name: 'api_device_data_get_uploader', methods: ['GET'])]
    public function __invoke(Request $request, string $xmlDirectory): JsonResponse
    {
        $apiKey = $request->headers->get('X-API-KEY');

        if (empty($apiKey) || $apiKey !== 'B7X4N9P6T3Q8W1KF') {
            return $this->json('Token is not valid', Response::HTTP_UNAUTHORIZED);
        }

        $filename = sprintf("%s.xml", $request->query->get('filename'));
        $content = htmlspecialchars(urldecode($request->query->get('content')), ENT_QUOTES, 'UTF-8');

        if (empty($filename) || empty($content)) {
            return $this->json('Invalid request.', Response::HTTP_BAD_REQUEST);
        }

        if (file_put_contents(sprintf("%s/%s", $xmlDirectory, $filename), $content)) {
            return $this->json('File uploaded successfully.', Response::HTTP_OK);
        }

        return $this->json('File upload failed.', Response::HTTP_INTERNAL_SERVER_ERROR);
    }

}