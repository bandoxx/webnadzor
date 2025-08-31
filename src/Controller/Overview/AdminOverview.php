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
            if ($user->isUser() || $user->isModerator() || $user->getClients()->contains($client) === false) {
                continue;
            }

            $clientId = $client->getId();
            $devices = $deviceRepository->findDevicesByClient($clientId);

            $data[$client->getId()] = [
                'id' => $clientId,
                'name' => $client->getName(),
                'address' => $client->getAddress(),
                'oib' => $client->getOIB(),
                'numberOfDevices' => 0, // Will count used sensors instead of devices
                'onlineDevices' => 0,   // Will count online sensors
                'offlineDevices' => 0,  // Will count offline sensors
                'overview' => $client->getOverviewViews(),
                'pdfLogo' => $client->getPdfLogo(),
                'mainLogo' => $client->getMainLogo(),
                'mapIcon' => $client->getMapMarkerIcon(),
                'devicePageView' => $client->getDevicePageView(),
                'alarms' => []
            ];

            $totalUsedSensors = 0;
            $onlineSensors = 0;
            $offlineSensors = 0;

            foreach ($devices as $device) {
                $deviceData = $deviceDataRepository->findLastRecordForDevice($device);

                if (!$deviceData) {
                    continue;
                }

                $isDeviceOnline = time() - $deviceData->getDeviceDate()->format('U') < $device->getIntervalTrashholdInSeconds();
                
                // Check Entry1 sensors - count as one sensor if any type is used
                if ($device->isTUsed(1) || $device->isRhUsed(1) || $device->isDUsed(1)) {
                    $totalUsedSensors++;
                    if ($isDeviceOnline) {
                        $onlineSensors++;
                    } else {
                        $offlineSensors++;
                    }
                }
                
                // Check Entry2 sensors - count as one sensor if any type is used
                if ($device->isTUsed(2) || $device->isRhUsed(2) || $device->isDUsed(2)) {
                    $totalUsedSensors++;
                    if ($isDeviceOnline) {
                        $onlineSensors++;
                    } else {
                        $offlineSensors++;
                    }
                }

                $alarms = $deviceAlarmRepository->findNumberOfActiveAlarmsForDevice($device);

                if ($alarms) {
                    $activeAlarm = $deviceAlarmRepository->findActiveAlarms($device);

                    foreach ($activeAlarm as $alarm) {
                        $path = null;

                        if ($alarm->getSensor()) {
                            $path = sprintf("<a href='%s'><b><u>Link do alarma</u></b></a>",
                                $router->generate('app_alarm_list', ['clientId' => $clientId, 'id' => $device->getId(), 'entry' => $alarm->getSensor()], UrlGeneratorInterface::ABSOLUTE_URL)
                            );
                        }

                        if ($path) {
                            $data[$clientId]['alarms'][] = sprintf(
                                "%s %s - %s",
                                $alarm->getMessage(),
                                $alarm->getTimeString(),
                                $path
                            );
                        } else {
                            $data[$clientId]['alarms'][] = $alarm->getMessage() . ' ' . $alarm->getTimeString();
                        }
                    }
                }
            }

            $data[$clientId]['numberOfDevices'] = $totalUsedSensors;
            $data[$clientId]['onlineDevices'] = $onlineSensors;
            $data[$clientId]['offlineDevices'] = $offlineSensors;
        }

        return $this->render('v2/overview/admin.html.twig', [
            'clients' => $data,
            'smtp' => $smtpRepository->findOneBy([])
        ]);
    }
}