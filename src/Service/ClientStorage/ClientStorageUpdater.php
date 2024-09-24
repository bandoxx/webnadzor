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
                $device['background'][$i] === 'true'
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

    public function updateDigitalEntryInputs(ClientStorage $clientStorage, array $digitalEntries): void
    {
        $digitalEntriesCount = count($digitalEntries['option']);

        foreach ($clientStorage->getDigitalEntryInput()->toArray() as $deviceInput) {
            $this->entityManager->remove($deviceInput);
        }

        for ($i = 0; $i < $digitalEntriesCount; $i++) {
            [$positionX, $positionY] = explode(',', $digitalEntries['position'][$i], 2);
            [$deviceId, $entry] = explode('-', $digitalEntries['option'][$i], 2);

            if (array_key_exists($deviceId, $this->devices) === false) {
                $this->devices[$deviceId] = $this->deviceRepository->find($deviceId);
            }

            $entity = $this->clientStorageInputFactory->createDigitalEntry(
                $clientStorage,
                $this->devices[$deviceId],
                $entry,
                $digitalEntries['font'][$i],
                $digitalEntries['colorOn'][$i],
                $digitalEntries['colorOff'][$i],
                $digitalEntries['textOn'][$i],
                $digitalEntries['textOff'][$i],
                $positionX,
                $positionY,
                $digitalEntries['background'][$i] === 'true'
            );

            $clientStorage->addDigitalEntryInput($entity);
            $this->entityManager->persist($entity);
        }

        $this->entityManager->flush();
    }
}