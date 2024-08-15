<?php

namespace App\Controller\Admin;

use App\Entity\Device;
use App\Entity\User;
use App\Repository\ClientRepository;
use App\Repository\DeviceAlarmRepository;
use App\Repository\DeviceDataRepository;
use App\Repository\DeviceRepository;
use App\Repository\SmtpRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\RouterInterface;

#[Route('/overview', name: 'admin_overview')]
class OverviewController extends AbstractController
{
    public function __invoke(ClientRepository $clientRepository, DeviceAlarmRepository $deviceAlarmRepository, DeviceRepository $deviceRepository, DeviceDataRepository $deviceDataRepository, RouterInterface $router, SmtpRepository $smtpRepository): RedirectResponse|\Symfony\Component\HttpFoundation\Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $clients = $clientRepository->findAllActive();

        if ($user->getClients()->count() === 1) {
            $clientId = $user->getclients()->first()->getId();

            return $this->redirectToRoute('client_overview', [
                'clientId' => $clientId,
            ]);
        }

        $data = [];
        foreach ($clients as $client) {
            if ($client->isDeleted() === true || in_array($user->getPermission(), [1, 2], true) || $user->getClients()->contains($client) === false) {
                continue;
            }

            /** @var Device[] $devices */
            $devices = $client->getDevice()->toArray();
            $clientId = $client->getId();

            $data[$client->getId()] = [
                'id' => $clientId,
                'name' => $client->getName(),
                'address' => $client->getAddress(),
                'oib' => $client->getOIB(),
                'numberOfDevices' => 0,
                'onlineDevices' => 0,
                'offlineDevices' => 0,
                'alarmsOn' => 0,
                'overview' => $client->getOverviewViews(),
                'pdfLogo' => $client->getPdfLogo(),
                'mainLogo' => $client->getMainLogo(),
                'mapIcon' => $client->getMapMarkerIcon(),
                'devicePageView' => $client->getDevicePageView(),
                'alarms' => []
            ];

            $totalDevices = count($devices);
            $onlineDevices = 0;

            foreach ($devices as $device) {
                $deviceData = $deviceDataRepository->findLastRecordForDevice($device);

                if (!$deviceData) {
                    continue;
                }

                if (time() - @strtotime($deviceData->getDeviceDate()->format('Y-m-d H:i:s')) < 4200) {
                    $onlineDevices++;
                }

                $alarms = $deviceAlarmRepository->findNumberOfActiveAlarmsForDevice($device);

                if ($alarms) {
                    $activeAlarm = $deviceAlarmRepository->findActiveAlarms($device);

                    foreach ($activeAlarm as $alarm) {
                        if ($alarm->getMessage()) {
                            $data[$clientId]['alarms'][] = $alarm->getMessage();
                        } else {
                            $data[$clientId]['alarms'][] =
                                sprintf("Mjerno mjesto: %s, Lokacija: %s, Tip alarma: '%s', upaljen od: %s",
                                    $device->getName(),
                                    $alarm->getLocation(),
                                    $alarm->getType(),
                                    $alarm->getDeviceDate()->format('d.m.Y H:i:s')
                                );
                        }
                    }
                }
            }

            $data[$clientId]['numberOfDevices'] = $totalDevices;
            $data[$clientId]['onlineDevices'] = $onlineDevices;
            $data[$clientId]['offlineDevices'] = $totalDevices - $onlineDevices;
            $data[$clientId]['alarmsOn'] = count($data[$clientId]['alarms']);
        }

        return $this->render('v2/overview/admin.html.twig', [
            'clients' => $data,
            'smtp' => $smtpRepository->findOneBy([])
        ]);
    }
}