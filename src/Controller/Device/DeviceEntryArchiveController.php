<?php

namespace App\Controller\Device;

use App\Repository\ClientRepository;
use App\Repository\DeviceDataRepository;
use App\Repository\DeviceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/admin/{clientId}/device/{id}/{entry}/archive', name: 'app_devicedataarchive_read', methods: 'GET')]
class DeviceEntryArchiveController extends AbstractController
{
    public function __invoke($clientId, $id, $entry, DeviceRepository $deviceRepository, DeviceDataRepository $deviceDataRepository): Response
    {
        $device = $deviceRepository->find($id);
        $deviceData = $deviceDataRepository->findLastRecordForDeviceAndEntry($device, $entry);

        return $this->render('device/device_sensor_archive.html.twig',[
            'device' => $device,
            'device_data' => $deviceData,
            'entry' => $entry
        ]);
    }

}