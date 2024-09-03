<?php

namespace App\Service\ClientStorage;

use App\Entity\ClientStorage;
use App\Factory\ClientStorageInputFactory;
use App\Repository\DeviceRepository;
use Doctrine\ORM\EntityManagerInterface;

class ClientStorageUpdater
{

    public function __construct(
        private EntityManagerInterface $entityManager,
        private ClientStorageInputFactory $clientStorageInputFactory,
        private DeviceRepository $deviceRepository,
        private array $devices = []
    ) {}

    public function updateDeviceInputs(ClientStorage $clientStorage, array $device): void
    {
        $deviceElementCount = count($device['option']);

        foreach ($clientStorage->getDeviceInput()->toArray() as $deviceInput) {
            $this->entityManager->remove($deviceInput);
        }

        for ($i = 0; $i < $deviceElementCount; $i++) {
            [$positionX, $positionY] = explode(',', $device['position'][$i], 2);
            [$deviceId, $entry, $type] = explode('-', $device['option'][$i], 3);

            if (array_key_exists($deviceId, $this->devices) === false) {
                $this->devices[$deviceId] = $this->deviceRepository->find($deviceId);
            }

            $entity = $this->clientStorageInputFactory->createDynamicText(
                $clientStorage,
                $this->devices[$deviceId],
                $entry,
                $type,
                $device['font'][$i],
                $device['color'][$i],
                $positionX,
                $positionY,
                $device['background'] === 'true'
            );

            $clientStorage->addDeviceInput($entity);
            $this->entityManager->persist($entity);
        }

        $this->entityManager->flush();
    }

    public function updateTextInputs(ClientStorage $clientStorage, array $text): void
    {
        $textElementCount = count($text['option']);

        foreach ($clientStorage->getTextInput()->toArray() as $textInput) {
            $this->entityManager->remove($textInput);
        }

        for ($i = 0; $i < $textElementCount; $i++) {
            [$positionX, $positionY] = explode(',', $text['position'][$i], 2);
            $entity = $this->clientStorageInputFactory->createText(
                $clientStorage,
                $text['option'][$i],
                $text['font'][$i],
                $text['color'][$i],
                $positionX,
                $positionY,
                $text['background'][$i] === 'true'
            );

            $clientStorage->addTextInput($entity);
            $this->entityManager->persist($entity);
        }

        $this->entityManager->flush();
    }
}