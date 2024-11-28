<?php

namespace App\Controller\Device\API;

use App\Entity\Client;
use App\Entity\Device;
use App\Service\DeviceUpdater;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
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
        DeviceUpdater $deviceUpdater,
        LoggerInterface $logger
    ): JsonResponse
    {
        $data = $request->getContent();

        $logger->error($data);

        try {
            $errors = $deviceUpdater->update($device, json_decode($data, true, 512, JSON_THROW_ON_ERROR));
        } catch (\Throwable $exception) {
            $logger->error($exception->getMessage(), [
                'data' => $data
            ]);
        }

        if ($errors) {
            return $this->json(['errors' => $errors], Response::HTTP_BAD_REQUEST);
        }

        return $this->json(null, Response::HTTP_OK);

    }

}