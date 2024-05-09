<?php

namespace App\Controller\Api;

use App\Repository\ClientRepository;
use App\Service\Device\UserAccess;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/map/markers', name: 'api_map_markers_all_clients')]
class MapMarkersAllClientsController extends AbstractController
{
    public function __invoke(ClientRepository $clientRepository, UserAccess $userAccess, $mapMarkerDirectory): JsonResponse
    {
        $clients = $clientRepository->findAll();
        $user = $this->getUser();
        $markers['places'] = [];
        $counter = [];

        foreach ($clients as $client) {
            $devices = $userAccess->getAccessibleDevices($client, $user);

            $icon = null;
            if ($client->getMapMarkerIcon()) {
                $icon = sprintf("%s/%s", $mapMarkerDirectory, $client->getMapMarkerIcon());
            }

            foreach ($devices as $device) {
                $locationHash = sha1($device->getLongitude() . $device->getLongitude());

                if (array_key_exists($locationHash, $markers['places'])) {
                    $counter[$locationHash]++;
                } else {
                    $counter[$locationHash] = 1;
                    $markers['places'][$locationHash] = [
                        'name' => $device->getName(),
                        'lat' => $device->getLatitude(),
                        'lng' => $device->getLongitude(),
                        'icon' => $icon
                    ];
                }

                if ($counter[$locationHash] > 1) {
                    $markers['places'][$locationHash]['name'] = sprintf("%s uređaja", $counter[$locationHash]);
                }
            }
        }

        return $this->json(['places' => array_values($markers['places']) ?? []], Response::HTTP_OK);
    }

}