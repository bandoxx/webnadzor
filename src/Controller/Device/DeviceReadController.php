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
    ): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        // Authorization check: non-root users must have access to this client
        if ($user->getPermission() !== User::ROLE_ROOT && !$user->getClients()->contains($client)) {
            throw $this->createAccessDeniedException('You do not have access to this client.');
        }

        if ($user->getPermission() === User::ROLE_USER) {
            $accesses = $user->getUserDeviceAccesses()->toArray();
            $devices = [];

            foreach ($accesses as $access) {
                $device = $access->getDevice();

                if (!$device) {
                    continue; // Skip invalid access entries
                }

                // Only include devices belonging to the requested client
                if ($device->getClient()?->getId() !== $client->getId()) {
                    continue;
                }

                if (array_key_exists($device->getId(), $devices) === false) {
                    $devices[$device->getId()] = $device;
                }
            }

            $devices = array_values($devices);
        } else {
            $devices = $deviceRepository->findBy(['client' => $client]);
        }

        // Batch load all data upfront to avoid N+1 queries
        $lastRecords = $deviceDataRepository->findLastRecordsByDevices($devices);
        $alarmCounts = $deviceAlarmRepository->findActiveAlarmsCountByDevices($devices);

        $deviceTable = [];

        foreach ($devices as $device) {
            $deviceId = $device->getId();
            $data = $lastRecords[$deviceId] ?? null;
            $numberOfAlarms = $alarmCounts[$deviceId] ?? 0;

            $online = false;

            if ($data !== null && $data->getDeviceDate() !== null) {
                $online = (time() - $data->getDeviceDate()->getTimestamp()) < $device->getIntervalThresholdInSeconds();
            }

            if (empty($device->getXmlName()) === false) {
                $identifier = $device->getXmlName();
            } else {
                $identifier = $device->getSerialNumber();
            }

            $deviceListModel = new DeviceListModel();
            $deviceListModel
                ->setId($device->getId())
                ->setXml($identifier)
                ->setName($device->getName())
                ->setOnline($online)
                ->setAlarm($numberOfAlarms > 0)
                ->setSignal($data?->getGsmSignal())
                ->setPower(number_format($data?->getVbat() ?? 0, 1))
                ->setBattery($data?->getBattery())
            ;

            $deviceTable[] = $deviceListModel;
        }

        return $this->render('v2/device/list.html.twig', [
            'devices_table' => $deviceTable,
        ]);
    }
}