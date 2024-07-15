<?php

namespace App\Controller\Client;

use App\Factory\ClientDeviceFactory;
use App\Repository\ClientRepository;
use App\Repository\ClientStorageRepository;
use App\Service\DeviceLocationHandler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/admin/{clientId}/{clientStorageId}/devices/save', name: 'save_devices')]

class SaveDevicesImageController extends AbstractController
{

    public function __invoke
    (
        int $clientId,
        int $clientStorageId,
        ClientRepository $clientRepository,
        ClientStorageRepository $clientStorageRepository,
        DeviceLocationHandler $deviceLocationHandler,
        Request $request,
        EntityManagerInterface $entityManager,
        ClientDeviceFactory $clientDeviceFactory
    ): Response
    {

        $clientStorage = $clientStorageRepository->find($clientStorageId);

        $clientDeviceFactory->create($request,$clientStorage,$entityManager);

        return new Response('success');
    }

}