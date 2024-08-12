<?php

namespace App\Controller\Device;

use App\Model\TemperatureType;
use App\Repository\DeviceIconRepository;
use App\Repository\DeviceRepository;
use App\Service\DeviceUpdater;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/admin/{clientId}/device/{deviceId}/edit', name: 'app_device_edit', methods: 'GET|POST')]
class DeviceEditController extends AbstractController
{

    public function __invoke(int $clientId, int $deviceId, Request $request, DeviceRepository $deviceRepository, DeviceIconRepository $deviceIconRepository, DeviceUpdater $deviceUpdater): RedirectResponse|Response
    {
        $error = [];
        $device = $deviceRepository->find($deviceId);

        if (!$device) {
            throw new NotFoundHttpException();
        }

        $icons = $deviceIconRepository->findAll();

        if ($request->getMethod() === 'POST') {
            // TODO: Permissions
            try {
                if ($this->getUser()->getPermission() >= 3) {
                    $deviceUpdater->update($device, $request->request->all());

                    return $this->redirectToRoute('app_device_edit', ['clientId' => $clientId, 'deviceId' => $deviceId]);
                }

                return $this->redirectToRoute('client_overview', ['clientId' => $clientId]);
            } catch (\Throwable $e) {
                return $this->redirectToRoute('app_device_edit', ['clientId' => $clientId, 'deviceId' => $deviceId]);
            }
        }

        return $this->render('v2/device/edit.html.twig', [
            'device' => $device,
            'icons' => $icons,
            'clientId' => $clientId,
            'errors' => $error,
            'temperature_type' => TemperatureType::getTypes()
        ]);

    }

}