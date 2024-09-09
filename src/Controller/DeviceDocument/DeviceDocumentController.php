<?php

namespace App\Controller\DeviceDocument;

use App\Entity\Device;
use App\Repository\DeviceDocumentRepository;
use App\Service\Device\DeviceDocumentHandler;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/admin/{clientId}/device/{deviceId}/{entry}/document', name: 'app_device_documents', methods: ['GET', 'POST'])]
class DeviceDocumentController extends AbstractController
{

    public function __invoke(
        int $clientId,
        #[MapEntity(id: 'deviceId')]
        Device $device,
        int $entry,
        DeviceDocumentRepository $deviceDocumentRepository,
        DeviceDocumentHandler $deviceDocumentHandler,
        Request $request,

    ): Response
    {
        if ($request->getMethod() === 'POST') {
            $year = $request->request->get('year');
            $documentNumber = $request->request->get('documentNumber');
            $sensorNumber = $request->request->get('sensorNumber');
            $document = $request->files->get('document');

            $fileName = $deviceDocumentHandler->upload($document);
            $deviceDocumentHandler->save($device, $entry, $fileName, $year, $documentNumber, $sensorNumber);
        }

        return $this->render('v2/device/document.html.twig', [
            'documents' => $deviceDocumentRepository->findBy(['device' => $device, 'entry' => $entry]),
        ]);
    }

}