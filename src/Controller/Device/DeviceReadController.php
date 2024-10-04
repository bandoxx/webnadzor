<?php

namespace App\Controller\Device;

use App\Entity\Client;
use App\Entity\User;
use App\Model\DeviceListModel;
use App\Repository\DeviceAlarmRepository;
use App\Repository\DeviceDataRepository;
use App\Repository\DeviceRepository;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/admin/{clientId}/devices', name: 'app_device_list')]
class DeviceReadController extends AbstractController
{
    public function __invoke(
        #[MapEntity(id: 'clientId')]
        Client $client,
        DeviceRepository $deviceRepository,
        DeviceAlarmRepository $deviceAlarmRepository,
        DeviceDataRepository $deviceDataRepository
    ): Response|NotFoundHttpException
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($user->getPermission() === 1) {
            $accesses = $user->getUserDeviceAccesses()->toArray();
            $devices = [];

            foreach ($accesses as $access) {
                $device = $access->getDevice();

                if (!$device) {
                    return $this->createNotFoundException("Device not found.");
                }

                if (array_key_exists($device->getId(), $devices) === false) {
                    $devices[$device->getId()] = $device;
                }
            }

            $devices = array_values($devices);
        } else {
            $devices = $deviceRepository->findBy(['client' => $client]);
        }

        $deviceTable = [];
        foreach ($devices as $device) {
            $data = $deviceDataRepository->findLastRecordForDevice($device);
            $numberOfAlarms = $deviceAlarmRepository->findNumberOfActiveAlarmsForDevice($device);
            $online = false;

            if ($data && time() - $data->getDeviceDate()?->format('U') < $device->getXmlIntervalInSeconds()) {
                $online = true;
            }

            $deviceListModel = new DeviceListModel();
            $deviceListModel
                ->setId($device->getId())
                ->setXml($device->getXmlName())
                ->setName($device->getName())
                ->setOnline($online)
                ->setAlarm($numberOfAlarms > 0)
                ->setSignal($data?->getGsmSignal())
                ->setPower(number_format($data?->getVbat(), 1))
                ->setBattery($data?->getBattery())
            ;

            $deviceTable[] = $deviceListModel;
        }

        return $this->render('v2/device/list.html.twig', [
            'devices_table' => $deviceTable,
        ]);
    }
}