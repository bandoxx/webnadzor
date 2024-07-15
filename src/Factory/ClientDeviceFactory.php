<?php

namespace App\Factory;

use App\Entity\ClientStorage;
use App\Entity\ClientStorageDevice;
use App\Entity\ClientStorageText;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

class ClientDeviceFactory
{

    public function create(Request $request, ClientStorage $clientStorage, EntityManagerInterface $entityManager)
    {
        $data = json_decode($request->getContent());


        foreach ($data as $item) {
            if ($item->update == 0){

                if ($item->type === 'device') {

                    $itemId = explode("-", $item->name);

                    $deviceId = $itemId[0];
                    $entry = $itemId[1];

                    $clientStorageDevice = new ClientStorageDevice();
                    $clientStorageDevice->setClientStorage($clientStorage);
                    $clientStorageDevice->setDeviceId($deviceId);
                    $clientStorageDevice->setEntry($entry);
                    $clientStorageDevice->setFontSize((int)$item->fontSize);
                    $clientStorageDevice->setFontColor($item->textColor);
                    $clientStorageDevice->setPositionX($item->x);
                    $clientStorageDevice->setPositionY($item->y);

                    $entityManager->persist($clientStorageDevice);
                } elseif ($item->type === 'text') {

                    $clientStorageDevice = new ClientStorageText();
                    $clientStorageDevice->setClientStorage($clientStorage);
                    $clientStorageDevice->setFontSize((int)$item->fontSize);
                    $clientStorageDevice->setFontColor($item->textColor);
                    $clientStorageDevice->setPlaceholderText($item->value);
                    $clientStorageDevice->setPositionX($item->x);
                    $clientStorageDevice->setPositionY($item->y);

                    $entityManager->persist($clientStorageDevice);
                }
            }
        }

        $entityManager->flush();

        return $clientStorageDevice;



    }

}