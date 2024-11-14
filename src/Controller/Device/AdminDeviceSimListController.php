<?php

namespace App\Controller\Device;

use App\Repository\DeviceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/device/sim-list', name: 'app_root_client_device_sim_list', methods: 'GET')]
class AdminDeviceSimListController extends AbstractController
{

    public function __invoke(DeviceRepository $deviceRepository): Response
    {
        $devices = $deviceRepository->findActiveDevices();
        $table = [];

        foreach ($devices as $device) {
            $table[] = [
                'xml' => $device->getXmlName(),
                'address' => $device->getClient()->getAddress(),
                'sim_number' => $device->getSimPhoneNumber(),
                'sim_provider' => $device->getSimCardProvider()
            ];
        }

        return $this->render('v2/device/device_sim_list.html.twig', [
            'list' => $table
        ]);
    }

}