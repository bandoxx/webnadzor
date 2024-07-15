<?php

namespace App\Controller\Client;

use App\Repository\ClientRepository;
use App\Repository\ClientStorageRepository;
use App\Repository\DeviceRepository;
use App\Service\PermissionChecker;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route(path: '/admin/{clientId}/{clientStorageId}/devices', name: 'client_devices_overview')]
class DisplayDeviceImageController extends AbstractController
{

    public function __invoke(
        int $clientId,
        int $clientStorageId,
        ClientRepository $clientRepository,
        ClientStorageRepository $clientStorageRepository,
        DeviceRepository $deviceRepository,
        SerializerInterface $serializer

    ): Response
    {
        $client = $clientRepository->find($clientId);

        if (!$client || PermissionChecker::isValid($this->getUser(), $client) === false) {
            throw $this->createAccessDeniedException();
        }

        $clientStorage = $clientStorageRepository->find($clientStorageId);

        $devices = $deviceRepository->findDevicesIdEntryByClient($clientId);

        $devideData = [];
        foreach ($devices as $device){
            $entry1 = json_decode($device['entry1']);
            $entry2 = json_decode($device['entry2']);
            if ($entry1->t_use && $entry2->t_use){
                $devideData[$device['id']]['entry1'] = [
                    'id' => $device['id'],
                    'entry' => 1,
                    'name' => $entry1->t_name,
                    'location' => $entry1->t_location,
                ];
                $devideData[$device['id']]['entry2'] = [
                    'id' => $device['id'],
                    'entry' => 2,
                    'name' => $entry2->t_name,
                    'location' => $entry2->t_location,
                ];

            }
        }


        $jsonData = $serializer->serialize($devideData, 'json');


        return $this->render('device/client_devices.html.twig',[
            'clientId' => $client,
            'clientStorage' => $clientStorage,
            'clientDevices' => $jsonData
        ]);
    }

}