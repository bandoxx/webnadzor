<?php

namespace App\Controller\Device;

use App\Repository\DeviceIconRepository;
use App\Repository\DeviceRepository;
use App\Service\DeviceUpdater;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/admin/{clientId}/device/{id}/edit', name: 'app_device_edit', methods: 'GET|POST')]
class DeviceEditController extends AbstractController
{

    public function __invoke($clientId, $id, Request $request, DeviceRepository $deviceRepository, DeviceIconRepository $deviceIconRepository, DeviceUpdater $deviceUpdater): JsonResponse|Response
    {
        $error = [];
        $device = $deviceRepository->find($id);

        $icons = $deviceIconRepository->findBy(['client' => $clientId]);
        if ($request->getMethod() === 'POST') {
            try {
                $device = $deviceUpdater->update($device, $request->request->all());

                return $this->json(true, Response::HTTP_ACCEPTED);
            } catch (\Throwable $e) {
                $error = [$e->getMessage()];
            }
        }

        return $this->render('device/edit.html.twig', [
            'device' => $device,
            'icons' => $icons,
            'clientId' => $clientId,
            'errors' => $error
        ]);

    }

}