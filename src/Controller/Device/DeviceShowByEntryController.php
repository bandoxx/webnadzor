<?php

namespace App\Controller\Device;

use App\Repository\DeviceDataRepository;
use App\Repository\DeviceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/admin/{clientId}/device/{id}/{entry}/show', methods: 'GET', name: 'app_device_entry_show')]
class DeviceShowByEntryController extends AbstractController
{

    public function __invoke($clientId, $id, $entry, DeviceRepository $deviceRepository, DeviceDataRepository $deviceDataRepository)
    {
        $device = $deviceRepository->find($id);
        $deviceData = $deviceDataRepository->findLastRecordForDeviceAndEntry($device, $entry);

        return $this->render('device/device_sensor_show.html.twig', [
            'device' => $device,
            'device_data' => $deviceData,
            'entry' => $entry
        ]);
    }

}