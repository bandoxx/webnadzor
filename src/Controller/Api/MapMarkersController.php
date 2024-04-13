<?php

namespace App\Controller\Api;

use App\Repository\ClientRepository;
use App\Service\Device\UserAccess;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/map/markers/{clientId}', name: 'api_map_markers')]
class MapMarkersController extends AbstractController
{
    public function __invoke($clientId, ClientRepository $clientRepository, UserAccess $userAccess): JsonResponse
    {
        $client = $this->getUser()->getClient();

        if (!$client) {
            $client = $clientRepository->find($clientId);
        }

        $devices = $userAccess->getAccessibleDevices($client, $this->getUser());

        $markers['places'] = [];
        $counter = [];
        foreach ($devices as $device) {
            $locationHash = sha1($device->getLongitude() . $device->getLongitude());

            if (array_key_exists($locationHash, $markers['places'])) {
                $counter[$locationHash]++;
            } else {
                $counter[$locationHash] = 1;
                $markers['places'][$locationHash] = [
                    'name' => $device->getName(),
                    'lat' => $device->getLatitude(),
                    'lng' => $device->getLongitude()
                ];
            }

            if ($counter[$locationHash] > 1) {
                $markers['places'][$locationHash]['name'] = sprintf("%s uredjaja", $counter[$locationHash]);
            }
        }

        return $this->json(['places' => array_values($markers['places'])], Response::HTTP_OK);
    }

}