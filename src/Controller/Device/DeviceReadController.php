<?php

namespace App\Controller\Device;

use App\Model\DeviceListModel;
use App\Repository\DeviceDataRepository;
use App\Repository\DeviceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/admin/{clientId}/devices', name: 'app_device_list')]
class DeviceReadController extends AbstractController
{
    public function __invoke($clientId, DeviceRepository $deviceRepository, DeviceDataRepository $deviceDataRepository)
    {
        $devices = $deviceRepository->findBy(['client' => $clientId]);

        $deviceTable = [];
        foreach ($devices as $device) {
            $data = $deviceDataRepository->findLastRecordForDevice($device);
            $online = false;

            if ($data && time() - @strtotime($data->getDeviceDate()->format('Y-m-d H:i:s')) < 4200) {
                $online = true;
            }

            $deviceListModel = new DeviceListModel();
            $deviceListModel
                ->setId($device->getId())
                ->setXml($device->getXmlName())
                ->setName($device->getName())
                ->setOnline($online)
                ->setAlarm(false)
                ->setSignal($data?->getGsmSignal())
                ->setPower(number_format($data?->getVbat(), 1))
                ->setBattery($data?->getBattery())
            ;

            $deviceTable[] = $deviceListModel;
        }

        return $this->render('device/list.html.twig', [
            'devices_table' => $deviceTable,
            'client_id' => $clientId
        ]);
    }
}