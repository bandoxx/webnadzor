<?php

namespace App\Controller\Device;

use App\Repository\DeviceDataRepository;
use App\Repository\DeviceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/admin/{clientId}/device/{id}/show', name: 'app_device_show', methods: 'GET')]
class DeviceShowController extends AbstractController
{

    public function __invoke(int $clientId, int $id, DeviceRepository $deviceRepository): Response
    {
        $device = $deviceRepository->find($id);

        return $this->render('device/edit.html.twig', [
            'device' => $device
        ]);
    }

}