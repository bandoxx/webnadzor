<?php

namespace App\Controller\Client;

use App\Repository\ClientRepository;
use App\Repository\ClientStorageDeviceRepository;
use App\Repository\ClientStorageRepository;
use App\Repository\ClientStorageTextRepository;
use App\Repository\DeviceRepository;
use App\Service\DeviceLocationHandler;
use App\Service\PermissionChecker;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route(path: '/admin/{clientId}/{clientStorageId}/devices/update', name: 'update_devices')]

class UpdateDeviceImageController extends AbstractController
{
    public function __invoke
    (
        int $clientId,
        int $clientStorageId,
        ClientRepository $clientRepository,
        ClientStorageRepository $clientStorageRepository,
        ClientStorageDeviceRepository $clientStorageDeviceRepository,
        ClientStorageTextRepository $clientStorageTextRepository,
        DeviceLocationHandler $deviceLocationHandler,
        Request $request,
        EntityManagerInterface $entityManager,
        DeviceRepository $deviceRepository,
        SerializerInterface $serializer
    ): Response
    {
        $client = $clientRepository->find($clientId);


        if (!$client || PermissionChecker::isValid($this->getUser(), $client) === false) {
            throw $this->createAccessDeniedException();
        }

        $clientStorageDevices = $clientStorageDeviceRepository->getByClientId($clientStorageId);
        $clientStorageTexts = $clientStorageTextRepository->getByClientId($clientStorageId);
        $clientStorage = $clientStorageRepository->find($clientStorageId);

        $mergedClientStorageItemPositions = $serializer->serialize(array_merge($clientStorageDevices, $clientStorageTexts), 'json');

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

        return $this->render('device/update_client_devices.html.twig',[
            'clientId' => $client,
            'clientStorage' => $clientStorage,
            'clientStorageDevices' => $mergedClientStorageItemPositions,
            'clientDevices' => $serializer->serialize($devideData, 'json')
        ]);
    }
}