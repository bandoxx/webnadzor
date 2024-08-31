<?php

namespace App\Service\ClientStorage;

use App\Entity\Client;
use App\Entity\ClientStorage;
use App\Service\Image\ClientStorageUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

class ClientStorageHandler
{

    public function __construct(
        private ClientStorageUpdater $clientStorageUpdater,
        private ClientStorageUploader $clientStorageUploader,
        private EntityManagerInterface $entityManager
    ) {}


    public function update(ClientStorage $clientStorage, Request $request)
    {
        $inputs = $request->request->all();

        $image = $request->files->get('clientStorageImage');

        if ($image) {
            $this->clientStorageUploader->uploadAndSave($image, $clientStorage);
        }

        $clientStorage->setName($inputs['client_storage_name'] ?? '');

        if ($clientStorage->getId() === null) {
            $this->entityManager->persist($clientStorage);
            $this->entityManager->flush();
        }

        if (isset($inputs['text']['option'])) {
            $this->clientStorageUpdater->updateTextInputs($clientStorage, $inputs['text']);
        }

        if (isset($inputs['device']['option'])) {
            $this->clientStorageUpdater->updateDeviceInputs($clientStorage, $inputs['device']);
        }
    }

    public function removeClientStorage(ClientStorage $clientStorage): void
    {
        foreach ($clientStorage->getDeviceInput()->toArray() as $deviceInput) {
            $this->entityManager->remove($deviceInput);
        }

        foreach ($clientStorage->getTextInput()->toArray() as $textInput) {
            $this->entityManager->remove($textInput);
        }

        $this->entityManager->remove($clientStorage);
        $this->entityManager->flush();
    }

    public function getDropDown(Client $client): array
    {
        $devices = $client->getDevice()->toArray();
        $list = [];

        foreach ($devices as $device) {
            for ($entry = 1; $entry <= 2; $entry++) {
                $text = sprintf("%s, %s, %s", $device->getName(), $device->getEntryData($entry)['t_location'], $device->getEntryData($entry)['t_name']);
                if ($device->isTUsed($entry)) {
                    $list[] = [
                        'value' => sprintf("%s-%s-t", $device->getId(), $entry),
                        'text' => sprintf("%s - Temperatura", $text),
                    ];
                }

                if ($device->isRhUsed($entry)) {
                    $list[] = [
                        'value' => sprintf("%s-%s-rh", $device->getId(), $entry),
                        'text' => sprintf("%s - Vlaga", $text),
                    ];
                }
            }
        }

        return $list;
    }
}