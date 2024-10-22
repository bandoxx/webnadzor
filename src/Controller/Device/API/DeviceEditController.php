<?php

namespace App\Controller\Device\API;

use App\Entity\Client;
use App\Entity\Device;
use App\Entity\User;
use App\Model\TemperatureType;
use App\Repository\DeviceIconRepository;
use App\Service\DeviceUpdater;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/api/{clientId}/device/{deviceId}/edit', name: 'api_device_edit', methods: 'POST')]
class DeviceEditController extends AbstractController
{

    public function __invoke(
        #[MapEntity(id: 'clientId')]
        Client $client,
        #[MapEntity(id: 'deviceId')]
        Device $device,
        Request $request,
        DeviceUpdater $deviceUpdater
    ): RedirectResponse|Response
    {
        $errors = $deviceUpdater->update($device, json_decode($request->getContent(), true));

        if ($errors) {
            return $this->json(['errors' => $errors], Response::HTTP_BAD_REQUEST);
        }

        return $this->json(null, Response::HTTP_OK);

    }

}