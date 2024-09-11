<?php

namespace App\Controller\Overview;

use App\Entity\User;
use App\Repository\ClientRepository;
use App\Repository\DeviceAlarmRepository;
use App\Repository\DeviceDataRepository;
use App\Repository\DeviceRepository;
use App\Repository\SmtpRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route('/overview', name: 'admin_overview')]
class AdminOverview extends AbstractController
{
    public function __invoke(ClientRepository $clientRepository, DeviceAlarmRepository $deviceAlarmRepository, DeviceRepository $deviceRepository, DeviceDataRepository $deviceDataRepository, UrlGeneratorInterface $router, SmtpRepository $smtpRepository): RedirectResponse|\Symfony\Component\HttpFoundation\Response
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
            if ($client->isDeleted() === true || $user->isUser() || $user->isModerator() || $user->getClients()->contains($client) === false) {
                continue;
            }

            $clientId = $client->getId();
            $devices = $deviceRepository->findDevicesByClient($clientId);

            $data[$client->getId()] = [
                'id' => $clientId,
                'name' => $client->getName(),
                'address' => $client->getAddress(),
                'oib' => $client->getOIB(),
                'numberOfDevices' => count($devices),
                'onlineDevices' => 0,
                'offlineDevices' => 0,
                'overview' => $client->getOverviewViews(),
                'pdfLogo' => $client->getPdfLogo(),
                'mainLogo' => $client->getMainLogo(),
                'mapIcon' => $client->getMapMarkerIcon(),
                'devicePageView' => $client->getDevicePageView(),
                'alarms' => []
            ];

            $onlineDevices = 0;

            foreach ($devices as $device) {
                $deviceData = $deviceDataRepository->findLastRecordForDevice($device);

                if (!$deviceData) {
                    continue;
                }

                if (time() - $deviceData->getDeviceDate()->format('U') < $device->getXmlIntervalInSeconds()) {
                    $onlineDevices++;
                }

                $alarms = $deviceAlarmRepository->findNumberOfActiveAlarmsForDevice($device);

                if ($alarms) {
                    $activeAlarm = $deviceAlarmRepository->findActiveAlarms($device);

                    foreach ($activeAlarm as $alarm) {
                        $path = sprintf("<a href='%s'><b><u>Link do alarma</u></b></a>",
                            $router->generate('app_alarm_list', ['clientId' => $clientId, 'id' => $device->getId()], UrlGeneratorInterface::ABSOLUTE_URL)
                        );

                        $data[$clientId]['alarms'][] = sprintf(
                            "%s - %s",
                            $alarm->getMessage(),
                            $path
                        );
                    }
                }
            }

            $data[$clientId]['onlineDevices'] = $onlineDevices;
            $data[$clientId]['offlineDevices'] = $data[$clientId]['numberOfDevices'] - $onlineDevices;
        }

        return $this->render('v2/overview/admin.html.twig', [
            'clients' => $data,
            'smtp' => $smtpRepository->findOneBy([])
        ]);
    }
}