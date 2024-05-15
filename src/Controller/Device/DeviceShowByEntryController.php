<?php

namespace App\Controller\Device;

use App\Factory\DeviceOverviewFactory;
use App\Repository\ClientRepository;
use App\Repository\DeviceDataRepository;
use App\Repository\DeviceIconRepository;
use App\Repository\DeviceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/admin/{clientId}/device/{id}/{entry}/show', name: 'app_device_entry_show', methods: 'GET')]
class DeviceShowByEntryController extends AbstractController
{

    public function __invoke(int $clientId, int $id, int $entry, DeviceOverviewFactory $deviceOverviewFactory, ClientRepository $clientRepository, DeviceRepository $deviceRepository, DeviceDataRepository $deviceDataRepository, DeviceIconRepository $deviceIconRepository): Response
    {
        $device = $deviceOverviewFactory->create($deviceRepository->find($id), $entry);

        return $this->render('device/device_sensor_show.html.twig', [
            'device' => $device,
            'entry' => $entry,
        ]);
    }

}