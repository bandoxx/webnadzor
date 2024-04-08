<?php

namespace App\Controller\Api;

use App\Repository\ClientRepository;
use App\Repository\DeviceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MapMarkersController extends AbstractController
{

    #[Route('/api/{clientId}/map/markers', name: 'map_markers')]
    public function getMarkers($clientId, ClientRepository $clientRepository, DeviceRepository $deviceRepository): JsonResponse
    {
        $client = $clientRepository->find($clientId);

        if (!$client) {
            throw $this->createNotFoundException();
        }

        $devices = $deviceRepository->findBy(['client' => $client]);

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