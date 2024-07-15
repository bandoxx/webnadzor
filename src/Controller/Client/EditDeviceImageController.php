<?php

namespace App\Controller\Client;

use App\Factory\ClientDeviceFactory;
use App\Repository\ClientRepository;
use App\Repository\ClientStorageDeviceRepository;
use App\Repository\ClientStorageRepository;
use App\Repository\ClientStorageTextRepository;
use App\Service\DeviceLocationHandler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/admin/{clientId}/{clientStorageId}/devices/edit', name: 'edit_devices', methods: 'PATCH')]

class EditDeviceImageController extends AbstractController
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
        ClientDeviceFactory $clientDeviceFactory
    ): Response
    {
        $client = $clientRepository->find($clientId);

        $clientStorage = $clientStorageRepository->find($clientStorageId);

        $data = json_decode($request->getContent());

        foreach ($data as $item) {
            if (isset($item->update) && $item->update == 1) {
                if ($item->type == 'text'){
                    $clientStorageText = $clientStorageTextRepository->find($item->id);

                    if (!$clientStorageText) {
                        throw $this->createNotFoundException('No client storage device found for id ' . $item->id);
                    }

                    $clientStorageText->setFontSize((int)$item->fontSize);
                    $clientStorageText->setFontColor($item->textColor);
                    $clientStorageText->setPlaceholderText($item->value);
                    if (!is_null($item->x) && !is_null($item->y)){
                        $clientStorageText->setPositionX($item->x);
                        $clientStorageText->setPositionY($item->y);
                    }

                    $entityManager->persist($clientStorageText);
                }
                else{

                    $itemId = explode("-", $item->name);

                    $deviceId = $itemId[0];
                    $entry = $itemId[1];


                    $clientStorageDevice = $clientStorageDeviceRepository->find($item->id);
//                $clientStorageDevice->setClientStorage($clientStorage);
                    $clientStorageDevice->setDeviceId($deviceId);
                    $clientStorageDevice->setEntry($entry);
                    $clientStorageDevice->setFontSize((int)$item->fontSize);
                    $clientStorageDevice->setFontColor($item->textColor);

                    if (!is_null($item->x) && !is_null($item->y)) {
                        $clientStorageDevice->setPositionX($item->x);
                        $clientStorageDevice->setPositionY($item->y);
                    }

                    $entityManager->persist($clientStorageDevice);

                }
                $entityManager->flush();


            } else {
                $clientDeviceFactory->create($request,$clientStorage,$entityManager);
            }
        }
        return new Response('success');

    }
}