<?php

namespace App\Controller\DeviceData;

use App\Service\ClientStorage\Types\DeviceTypesDropdown;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/device-data', name: 'device_data', methods: ['GET'])]
class DeviceDataViewController extends AbstractController
{
    public function __construct(
        private readonly DeviceTypesDropdown $deviceTypesDropdown
    ) {
    }

    public function __invoke(): Response
    {
        return $this->render('v2/device/device_data.html.twig', [
            'deviceTypesDropdown' => $this->deviceTypesDropdown->getAllDevices(),
        ]);
    }
}
