<?php

namespace App\Service\Device;

use App\Entity\Device;
use App\Entity\DeviceDocument;
use App\Factory\DeviceDocumentFactory;
use App\Service\FileUploader\Types\DeviceDocumentUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class DeviceDocumentHandler
{

    public function __construct(
        private readonly DeviceDocumentFactory $deviceDocumentFactory,
        private readonly DeviceDocumentUploader $deviceDocumentUploader,
        private readonly EntityManagerInterface $entityManager
    ) {}

    public function remove(DeviceDocument $deviceDocument): void
    {
        $this->entityManager->remove($deviceDocument);
        $this->entityManager->flush();
    }

    public function update(DeviceDocument $deviceDocument, int $year, string $documentNumber, string $serialNumber, ?string $fileName = null): void
    {
        $deviceDocument->setYear($year);
        $deviceDocument->setNumberOfDocument($documentNumber);
        $deviceDocument->setSerialSensorNumber($serialNumber);

        if ($fileName) {
            $deviceDocument->setFile($fileName);
        }

        $this->entityManager->flush();
    }

    public function save(Device $device, int $entry, string $fileName, int $year, string $documentNumber, string $serialNumber): void
    {
        $document = $this->deviceDocumentFactory->create($device, $entry, $fileName, $year, $documentNumber, $serialNumber);

        $this->entityManager->persist($document);
        $this->entityManager->flush();
    }

    public function upload(UploadedFile $uploadedFile): string
    {
        return $this->deviceDocumentUploader->upload($uploadedFile)->getFileName();
    }
}