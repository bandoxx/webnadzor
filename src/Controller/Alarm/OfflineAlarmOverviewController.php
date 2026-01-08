<?php

namespace App\Controller\Alarm;

use App\Entity\User;
use App\Repository\ClientRepository;
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
        DeviceRepository $deviceRepository,
        ClientRepository $clientRepository
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        if ($user->getPermission() !== User::ROLE_ROOT) {
            throw $this->createAccessDeniedException('Nemate pristup ovoj stranici.');
        }

        $clientId = $request->query->get('client');
        $deviceId = $request->query->get('device');
        $dateFrom = $request->query->get('dateFrom');
        $dateTo = $request->query->get('dateTo');

        $client = null;
        if ($clientId) {
            $client = $clientRepository->find((int) $clientId);
        }

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

        // Get clients for filter dropdown
        $clients = $clientRepository->findBy([], ['name' => 'ASC']);

        // Get devices for filter dropdown (with client JOIN)
        $devices = $deviceRepository->findForDropdown();

        // Build devices grouped by client for JavaScript
        $devicesByClient = [];
        foreach ($devices as $d) {
            $cId = $d->getClient()?->getId();
            if ($cId) {
                $devicesByClient[$cId][] = [
                    'id' => $d->getId(),
                    'name' => $d->getName(),
                    't1_name' => $d->getEntry1()['t_name'] ?? '-',
                    't2_name' => $d->getEntry2()['t_name'] ?? '-',
                ];
            }
        }

        // Limit results to prevent memory issues - uses JOINs to avoid N+1
        $alarms = $deviceAlarmRepository->findOfflineAlarms($client, $device, $dateFromObj, $dateToObj, 300);

        $table = [];
        foreach ($alarms as $alarm) {
            $alarmDevice = $alarm->getDevice();
            $client = $alarmDevice?->getClient();

            // Get sensor location without additional query
            $sensor = $alarm->getSensor();
            $location = 'Nema';
            if ($sensor && $alarmDevice) {
                $entryData = $alarmDevice->getEntryData((int) $sensor);
                $location = $entryData['t_name'] ?? 'Nema';
            }

            // Build device display name: name [ t1_name | t2_name ]
            $deviceDisplayName = '-';
            if ($alarmDevice) {
                $name = $alarmDevice->getName() ?? '';
                $t1Name = $alarmDevice->getEntry1()['t_name'] ?? '-';
                $t2Name = $alarmDevice->getEntry2()['t_name'] ?? '-';
                $deviceDisplayName = trim($name . ' [ ' . $t1Name . ' | ' . $t2Name . ' ]');
            }

            $table[] = [
                'id' => $alarm->getId(),
                'client_name' => $client?->getName() ?? '-',
                'device_name' => $deviceDisplayName,
                'type' => $alarm->getType(),
                'date' => $alarm->getDeviceDate(),
                'end_date' => $alarm->getEndDeviceDate(),
                'active' => $alarm->isActive(),
                'location' => $location,
            ];
        }

        return $this->render('v2/alarm/offline_alarm_overview.html.twig', [
            'alarms' => $table,
            'clients' => $clients,
            'devicesByClient' => $devicesByClient,
            'selectedClient' => $clientId,
            'selectedDevice' => $deviceId,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ]);
    }
}
