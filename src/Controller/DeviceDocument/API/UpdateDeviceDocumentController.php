<?php

namespace App\Controller\DeviceDocument\API;

use App\Entity\Device;
use App\Entity\DeviceDocument;
use App\Service\Device\DeviceDocumentHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/device-document/{id}/update', name: 'api_device_document_update', methods: ['POST'])]
class UpdateDeviceDocumentController extends AbstractController
{
    public function __invoke(
        DeviceDocument $deviceDocument,
        DeviceDocumentHandler $deviceDocumentHandler,
        Request $request
    ): RedirectResponse
    {
        $fileName = null;

        if ($document = $request->files->get('document')) {
            $fileName = $deviceDocumentHandler->upload($document);
        }

        $deviceDocumentHandler->update(
            $deviceDocument,
            $request->request->get('year'),
            $request->request->get('documentNumber'),
            $request->request->get('sensorNumber'),
            $fileName
        );

        /** @var Device $device */
        $device = $deviceDocument->getDevice();

        return $this->redirectToRoute('app_device_documents', [
            'deviceId' => $device->getId(),
            'entry' => $deviceDocument->getEntry(),
            'clientId' => $device->getClient()->getId()
        ]);
    }

}