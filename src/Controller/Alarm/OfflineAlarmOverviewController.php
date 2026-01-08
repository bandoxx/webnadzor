<?php

namespace App\Controller\Alarm;

use App\Entity\User;
use App\Repository\DeviceAlarmRepository;
use App\Repository\DeviceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/admin/offline-alarm-overview', name: 'app_offline_alarm_overview', methods: 'GET')]
class OfflineAlarmOverviewController extends AbstractController
{
    public function __invoke(
        Request $request,
        DeviceAlarmRepository $deviceAlarmRepository,
        DeviceRepository $deviceRepository
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        if ($user->getPermission() !== User::ROLE_ROOT) {
            throw $this->createAccessDeniedException('Nemate pristup ovoj stranici.');
        }

        $deviceId = $request->query->get('device');
        $dateFrom = $request->query->get('dateFrom');
        $dateTo = $request->query->get('dateTo');

        $device = null;
        if ($deviceId) {
            $device = $deviceRepository->find((int) $deviceId);
        }

        $dateFromObj = null;
        if ($dateFrom) {
            $dateFromObj = \DateTime::createFromFormat('Y-m-d', $dateFrom);
            if ($dateFromObj) {
                $dateFromObj->setTime(0, 0, 0);
            }
        }

        $dateToObj = null;
        if ($dateTo) {
            $dateToObj = \DateTime::createFromFormat('Y-m-d', $dateTo);
            if ($dateToObj) {
                $dateToObj->setTime(23, 59, 59);
            }
        }

        $alarms = $deviceAlarmRepository->findOfflineAlarms($device, $dateFromObj, $dateToObj);
        $devices = $deviceRepository->findAll();

        $table = [];
        foreach ($alarms as $alarm) {
            $alarmDevice = $alarm->getDevice();
            $client = $alarmDevice?->getClient();

            $table[] = [
                'id' => $alarm->getId(),
                'client_name' => $client?->getName() ?? '-',
                'device_name' => $alarmDevice?->getName() ?? '-',
                'type' => $alarm->getType(),
                'date' => $alarm->getDeviceDate(),
                'end_date' => $alarm->getEndDeviceDate(),
                'active' => $alarm->isActive(),
                'location' => $alarm->getLocation(),
            ];
        }

        return $this->render('v2/alarm/offline_alarm_overview.html.twig', [
            'alarms' => $table,
            'devices' => $devices,
            'selectedDevice' => $deviceId,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ]);
    }
}
