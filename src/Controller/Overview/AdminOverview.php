<?php

namespace App\Controller\Overview;

use App\Entity\User;
use App\Repository\AdminOverviewCacheRepository;
use App\Repository\ClientRepository;
use App\Repository\DeviceAlarmRepository;
use App\Repository\DeviceDataLastCacheRepository;
use App\Repository\DeviceRepository;
use App\Repository\SmtpRepository;
use App\Repository\UserDeviceAccessRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route('/overview', name: 'admin_overview')]
class AdminOverview extends AbstractController
{
    public function __invoke(
        ClientRepository $clientRepository,
        AdminOverviewCacheRepository $cacheRepository,
        SmtpRepository $smtpRepository,
        DeviceRepository $deviceRepository,
        DeviceDataLastCacheRepository $lastCacheRepository,
        DeviceAlarmRepository $deviceAlarmRepository,
        UserDeviceAccessRepository $userDeviceAccessRepository,
        UrlGeneratorInterface $router
    ): RedirectResponse|\Symfony\Component\HttpFoundation\Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $clients = $clientRepository->findAllActive();

        if ($user->getClients()->count() === 1) {
            $singleClient = $user->getClients()->first();
            $clientId = $singleClient->getId();

            // For regular users, redirect only if they have any device/sensor access for this client
            if ($user->getPermission() === User::ROLE_USER) {
                $hasAccess = (bool) $userDeviceAccessRepository->findOneBy(['user' => $user, 'client' => $singleClient]);
                if ($hasAccess) {
                    return $this->redirectToRoute('client_overview', [
                        'clientId' => $clientId,
                    ]);
                }
                // else: fall through to show the overview list (possibly with other clients)
            } else {
                return $this->redirectToRoute('client_overview', [
                    'clientId' => $clientId,
                ]);
            }
        }

        $data = [];
        foreach ($clients as $client) {
            // Preserve existing client filtering (skip moderators; include only user's clients)
            if ($user->isModerator() || $user->getClients()->contains($client) === false) {
                continue;
            }

            $clientId = $client->getId();

            // If ROOT (permission 4) - keep existing cached behavior as requested
            if ($user->getPermission() === User::ROLE_ROOT) {
                $cache = $cacheRepository->findOneByClient($client);

                $data[$client->getId()] = [
                    'id' => $clientId,
                    'name' => $client->getName(),
                    'address' => $client->getAddress(),
                    'oib' => $client->getOIB(),
                    'numberOfDevices' => $cache?->getNumberOfDevices() ?? 0,
                    'onlineDevices' => $cache?->getOnlineDevices() ?? 0,
                    'offlineDevices' => $cache?->getOfflineDevices() ?? 0,
                    'overview' => $client->getOverviewViews(),
                    'pdfLogo' => $client->getPdfLogo(),
                    'mainLogo' => $client->getMainLogo(),
                    'mapIcon' => $client->getMapMarkerIcon(),
                    'devicePageView' => $client->getDevicePageView(),
                    'alarms' => $cache?->getAlarms() ?? [],
                ];
                continue;
            }

            // Live compute using DeviceDataLastCache
            $devices = $deviceRepository->findDevicesByClient($clientId);

            $totalUsedSensors = 0;
            $onlineSensors = 0;
            $offlineSensors = 0;
            $alarmMessages = [];

            // Build access map for ROLE_USER (permission 1)
            $restrictByAccess = $user->getPermission() === User::ROLE_USER;
            $clientWideAccess = false;
            $allowedByDevice = [];
            if ($restrictByAccess) {
                $accessList = $userDeviceAccessRepository->findBy(['user' => $user, 'client' => $client]);
                foreach ($accessList as $access) {
                    $accDevice = $access->getDevice();
                    $sensor = $access->getSensor();
                    if (!$accDevice && $access->getClient()) {
                        // Client-wide access
                        $clientWideAccess = true;
                        // No need to process other entries; this grants all
                        continue;
                    }
                    if ($accDevice) {
                        $deviceId = $accDevice->getId();
                        if (!isset($allowedByDevice[$deviceId])) {
                            $allowedByDevice[$deviceId] = [];
                        }
                        if ($sensor === null) {
                            $allowedByDevice[$deviceId] = [1, 2];
                        } else {
                            if (!in_array($sensor, $allowedByDevice[$deviceId], true)) {
                                $allowedByDevice[$deviceId][] = (int) $sensor;
                            }
                        }
                    }
                }

                // Edge case: user assigned to client but with no device/sensor access -> skip this client entirely
                if (!$clientWideAccess && empty($allowedByDevice)) {
                    continue;
                }
            }

            foreach ($devices as $device) {
                $deviceId = $device->getId();

                foreach ([1, 2] as $entry) {
                    // Apply access filtering for ROLE_USER
                    if ($restrictByAccess && !$clientWideAccess) {
                        $allowed = isset($allowedByDevice[$deviceId]) && in_array($entry, $allowedByDevice[$deviceId], true);
                        if (!$allowed) {
                            continue;
                        }
                    }

                    // Consider only used sensors
                    if (!($device->isTUsed($entry) || $device->isRhUsed($entry) || $device->isDUsed($entry))) {
                        continue;
                    }

                    $totalUsedSensors++;

                    $cache = $lastCacheRepository->findOneBy(['device' => $device, 'entry' => $entry]);
                    if (!$cache || !$cache->getDeviceDate()) {
                        // No data means treat as offline
                        $offlineSensors++;
                        continue;
                    }

                    $isOnline = (time() - $cache->getDeviceDate()->format('U')) < $device->getIntervalTrashholdInSeconds();
                    if ($isOnline) {
                        $onlineSensors++;
                    } else {
                        $offlineSensors++;
                    }
                }

                // Collect alarms; if user has entry restrictions, include only alarms for allowed sensors
                $alarmsCount = $deviceAlarmRepository->findNumberOfActiveAlarmsForDevice($device);
                if ($alarmsCount) {
                    $activeAlarms = $deviceAlarmRepository->findActiveAlarms($device);
                    foreach ($activeAlarms as $alarm) {
                        $sensor = $alarm->getSensor();
                        if ($restrictByAccess && !$clientWideAccess) {
                            // If alarm is sensor-specific ensure access; sensor null means device-wide -> include if any access to any sensor of device
                            if ($sensor !== null) {
                                if (!(isset($allowedByDevice[$deviceId]) && in_array((int)$sensor, $allowedByDevice[$deviceId], true))) {
                                    continue;
                                }
                            } else {
                                if (!isset($allowedByDevice[$deviceId])) {
                                    continue;
                                }
                            }
                        }

                        $path = null;
                        if ($sensor) {
                            $path = sprintf("<a href='%s'><b><u>Link do alarma</u></b></a>",
                                $router->generate('app_alarm_list', [
                                    'clientId' => $clientId,
                                    'id' => $device->getId(),
                                    'entry' => $sensor,
                                ], UrlGeneratorInterface::ABSOLUTE_PATH)
                            );
                        }

                        if ($path) {
                            $alarmMessages[] = sprintf("%s %s - %s", $alarm->getMessage(), $alarm->getTimeString(), $path);
                        } else {
                            $alarmMessages[] = trim(($alarm->getMessage() ?? '') . ' ' . $alarm->getTimeString());
                        }
                    }
                }
            }

            $data[$client->getId()] = [
                'id' => $clientId,
                'name' => $client->getName(),
                'address' => $client->getAddress(),
                'oib' => $client->getOIB(),
                'numberOfDevices' => $totalUsedSensors,
                'onlineDevices' => $onlineSensors,
                'offlineDevices' => $offlineSensors,
                'overview' => $client->getOverviewViews(),
                'pdfLogo' => $client->getPdfLogo(),
                'mainLogo' => $client->getMainLogo(),
                'mapIcon' => $client->getMapMarkerIcon(),
                'devicePageView' => $client->getDevicePageView(),
                'alarms' => $alarmMessages,
            ];
        }

        return $this->render('v2/overview/admin.html.twig', [
            'clients' => $data,
            'smtp' => $smtpRepository->findOneBy([])
        ]);
    }
}