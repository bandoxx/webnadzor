<?php

namespace App\Controller\Admin;

use App\Entity\Device;
use App\Repository\ClientRepository;
use App\Repository\DeviceAlarmRepository;
use App\Repository\DeviceDataRepository;
use App\Repository\DeviceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;

#[Route('/overview', name: 'admin_overview')]
class OverviewController extends AbstractController
{
    public function __invoke(ClientRepository $clientRepository, DeviceAlarmRepository $deviceAlarmRepository, DeviceRepository $deviceRepository, DeviceDataRepository $deviceDataRepository, RouterInterface $router): RedirectResponse|\Symfony\Component\HttpFoundation\Response
    {
        if ($this->getUser()->getPermission() !== 4) {
            return $this->redirectToRoute('client_overview', [
                'clientId' => $this->getUser()->getClient()->getId(),
            ]);
        }

        $clients = $clientRepository->findAll();
        $data = [];
        foreach ($clients as $client) {
            /** @var Device[] $devices */
            $devices = $client->getDevice()->toArray();
            $clientId = $client->getId();

            $data[$client->getId()] = [
                'id' => $clientId,
                'name' => $client->getName(),
                'numberOfDevices' => 0,
                'onlineDevices' => 0,
                'offlineDevices' => 0,
                'alarmsOn' => 0,
                'overview' => $client->getOverviewViews(),
                'pdfLogo' => $client->getPdfLogo(),
                'mainLogo' => $client->getMainLogo(),
                'mapIcon' => $client->getMapMarkerIcon()
            ];

            $totalDevices = count($devices);
            $onlineDevices = 0;
            $activeAlarms = 0;

            foreach ($devices as $device) {
                $deviceData = $deviceDataRepository->findLastRecordForDevice($device);

                if (!$deviceData) {
                    continue;
                }

                if (time() - @strtotime($deviceData->getDeviceDate()->format('Y-m-d H:i:s')) < 4200) {
                    $onlineDevices++;
                }

                $activeAlarms += $deviceAlarmRepository->findNumberOfActiveAlarmsForDevice($device);
            }

            $data[$clientId]['numberOfDevices'] = $totalDevices;
            $data[$clientId]['onlineDevices'] = $onlineDevices;
            $data[$clientId]['offlineDevices'] = $totalDevices - $onlineDevices;
            $data[$clientId]['alarmsOn'] = $activeAlarms;
        }

        return $this->render('overview/admin.html.twig', [
            'clients' => $data,
        ]);
    }
}