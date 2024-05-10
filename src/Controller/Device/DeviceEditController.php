<?php

namespace App\Controller\Device;

use App\Model\TemperatureType;
use App\Repository\DeviceIconRepository;
use App\Repository\DeviceRepository;
use App\Service\DeviceUpdater;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/admin/{clientId}/device/{deviceId}/edit', name: 'app_device_edit', methods: 'GET|POST')]
class DeviceEditController extends AbstractController
{

    public function __invoke(int $clientId, int $deviceId, Request $request, DeviceRepository $deviceRepository, DeviceIconRepository $deviceIconRepository, DeviceUpdater $deviceUpdater): JsonResponse|Response
    {
        $error = [];
        $device = $deviceRepository->find($deviceId);

        $icons = $deviceIconRepository->findBy(['client' => $clientId]);
        if ($request->getMethod() === 'POST') {
            // TODO: Permissions
            try {
                if ($this->getUser()->getPermission() >= 3) {
                    $deviceUpdater->update($device, $request->request->all());

                    return $this->json(true, Response::HTTP_ACCEPTED);
                }

                return $this->json(false, Response::HTTP_UNAUTHORIZED);
            } catch (\Throwable $e) {
                $error = [$e->getMessage()];

                return $this->json($error, Response::HTTP_BAD_REQUEST);
            }
        }

        return $this->render('device/edit.html.twig', [
            'device' => $device,
            'icons' => $icons,
            'clientId' => $clientId,
            'errors' => $error,
            'temperature_type' => TemperatureType::getTypes()
        ]);

    }

}