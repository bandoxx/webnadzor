<?php

namespace App\Service\ClientStorage;

use App\Entity\Client;
use App\Entity\ClientStorage;
use App\Service\Image\ClientImage\ScadaImageHandler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

class ClientStorageHandler
{

    public function __construct(
        private ClientStorageUpdater $clientStorageUpdater,
        private ScadaImageHandler $scadaImageHandler,
        private EntityManagerInterface $entityManager
    ) {}


    public function update(ClientStorage $clientStorage, Request $request): void
    {
        $inputs = $request->request->all();

        $image = $request->files->get('clientStorageImage');

        if ($image) {
            $fileName = $this->scadaImageHandler->upload($image, $clientStorage);
            $this->scadaImageHandler->save($clientStorage, $fileName);
        }

        $clientStorage->setName($inputs['client_storage_name'] ?? '');

        if ($clientStorage->getId() === null) {
            $this->entityManager->persist($clientStorage);
            $this->entityManager->flush();
        }

        $this->clientStorageUpdater->updateTextInputs($clientStorage, isset($inputs['text']['option']) ? $inputs['text'] : []);
        $this->clientStorageUpdater->updateDeviceInputs($clientStorage, isset($inputs['device']['option']) ? $inputs['device'] : []);
        $this->clientStorageUpdater->updateDigitalEntryInputs($clientStorage, isset($inputs['digitalEntry']['option']) ? $inputs['digitalEntry'] : []);
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
}