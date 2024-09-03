<?php

namespace App\Controller\Device;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/admin/{clientId}/device/{deviceId}/{entry}/document', name: 'app_device_documents', methods: 'GET')]
class DeviceDocumentController extends AbstractController
{

    public function __invoke(int $clientId, int $deviceId, int $entry): Response
    {
        return $this->render('v2/device/document.html.twig');
    }

}