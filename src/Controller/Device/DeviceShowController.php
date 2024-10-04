<?php

namespace App\Controller\Device;

use App\Entity\Client;
use App\Entity\Device;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/admin/{clientId}/device/{deviceId}/show', name: 'app_device_show', methods: 'GET')]
class DeviceShowController extends AbstractController
{

    public function __invoke(
        #[MapEntity(id: 'clientId')]
        Client $client,
        #[MapEntity(id: 'deviceId')]
        Device $device
    ): Response
    {
        return $this->render('v1/device/edit.html.twig', [
            'device' => $device
        ]);
    }

}