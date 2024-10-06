<?php

namespace App\Controller\DeviceDocument\API;

use App\Entity\Device;
use App\Entity\DeviceDocument;
use App\Service\Device\DeviceDocumentHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/device-document/{id}/delete', name: 'api_device_document_delete', methods: ['POST'])]
class DeleteDeviceDocumentController extends AbstractController
{
    public function __invoke(DeviceDocument $deviceDocument, string $deviceDocumentDirectory, DeviceDocumentHandler $deviceDocumentHandler): RedirectResponse
    {
        $location = sprintf('%s/%s',
            $deviceDocumentDirectory,
            $deviceDocument->getFile(),
        );

        if (file_exists($location)) {
            unlink($location);
        }

        /** @var Device $device */
        $device = $deviceDocument->getDevice();
        $entry = $deviceDocument->getEntry();
        $clientId = $device->getClient()->getId();

        $deviceDocumentHandler->remove($deviceDocument);

        return $this->redirectToRoute('app_device_documents', [
            'deviceId' => $device->getId(),
            'entry' => $entry,
            'clientId' => $clientId
        ]);
    }

}