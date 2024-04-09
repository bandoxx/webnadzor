<?php

namespace App\Controller\Api;

use App\Service\Device\UserAccess;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MapMarkersController extends AbstractController
{

    #[Route('/api/map/markers', name: 'map_markers')]
    public function getMarkers(UserAccess $userAccess): JsonResponse
    {
        $devices = $userAccess->getAccessibleDevices($this->getUser());

        $markers['places'] = [];

        foreach ($devices as $device) {
            $markers['places'][] = [
                'name' => $device->getName(),
                'lat' => $device->getLatitude(),
                'lng' => $device->getLongitude()
            ];
        }

        return $this->json($markers, Response::HTTP_OK);
    }

}