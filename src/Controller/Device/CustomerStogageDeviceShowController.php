<?php

namespace App\Controller\Device;

use App\Repository\ClientStorageDeviceRepository;
use App\Repository\ClientStorageRepository;
use App\Repository\ClientStorageTextRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/admin/{clientId}/clientstorage/{clientStorageId}/show', name: 'app_client_storage_device_entry_show', methods: 'GET')]

class CustomerStogageDeviceShowController extends AbstractController
{

    public function __invoke(int $clientId, int $clientStorageId,
        ClientStorageRepository $clientStorageRepository,
        ClientStorageDeviceRepository $clientStorageDeviceRepository,
        ClientStorageTextRepository $clientStorageTextRepository,
    ): Response
    {

        $clientStorage = $clientStorageRepository->find($clientStorageId);

        $clientStorageDevices = $clientStorageDeviceRepository->getByClientId($clientStorageId);
        $clientStorageText = $clientStorageTextRepository->getByClientId($clientStorageId);

        $mergedClientStorageDevices = array_merge($clientStorageDevices,$clientStorageText);

        return $this->render('device/client_storage_device_show.html.twig', [
            'clientStorage' => $clientStorage,
            'clientStorageDevices' => $mergedClientStorageDevices
        ]);
    }

}