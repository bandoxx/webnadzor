<?php

namespace App\Service\Overview;

use App\Entity\User;
use App\Repository\AdminOverviewCacheRepository;
use App\Repository\ClientRepository;
use App\Repository\DeviceAlarmRepository;
use App\Repository\DeviceDataLastCacheRepository;
use App\Repository\DeviceRepository;
use App\Repository\UserDeviceAccessRepository;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class AdminOverviewService
{
    public function __construct(
        private readonly ClientRepository $clientRepository,
        private readonly AdminOverviewCacheRepository $cacheRepository,
        private readonly DeviceRepository $deviceRepository,
        private readonly DeviceDataLastCacheRepository $lastCacheRepository,
        private readonly DeviceAlarmRepository $deviceAlarmRepository,
        private readonly UserDeviceAccessRepository $userDeviceAccessRepository,
        private readonly UrlGeneratorInterface $router,
    ) {}

    /**
     * Returns clientId for redirect if should redirect; otherwise null.
     */
    public function getRedirectClientId(User $user): ?int
    {
        if ($user->getClients()->count() !== 1) {
            return null;
        }

        $singleClient = $user->getClients()->first();
        $clientId = $singleClient->getId();

        if ($user->getPermission() === User::ROLE_USER) {
            $hasAccess = (bool) $this->userDeviceAccessRepository->findOneBy(['user' => $user, 'client' => $singleClient]);
            return $hasAccess ? $clientId : null;
        }

        return $clientId;
    }

    /**
     * Build admin overview clients data for the given user.
     * Returns array keyed by client id with overview data.
     */
    public function buildOverview(User $user): array
    {
        $data = [];
        $clients = $this->clientRepository->findAllActive();

        foreach ($clients as $client) {
            // Skip moderators; include only user's clients
            if ($user->isModerator() || $user->getClients()->contains($client) === false) {
                continue;
            }

            $clientId = $client->getId();

            // ROOT uses cached summary
            if ($user->getPermission() === User::ROLE_ROOT) {
                $cache = $this->cacheRepository->findOneByClient($client);

                $data[$clientId] = [
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

            // Non-root: compute live
            $devices = $this->deviceRepository->findDevicesByClient($clientId);

            $totalUsedSensors = 0;
            $onlineSensors = 0;
            $offlineSensors = 0;
            $alarmMessages = [];

            // Access filtering for ROLE_USER
            $restrictByAccess = $user->getPermission() === User::ROLE_USER;
            $clientWideAccess = false;
            $allowedByDevice = [];

            if ($restrictByAccess) {
                $accessList = $this->userDeviceAccessRepository->findBy(['user' => $user, 'client' => $client]);
                foreach ($accessList as $access) {
                    $accDevice = $access->getDevice();
                    $sensor = $access->getSensor();
                    if (!$accDevice && $access->getClient()) {
                        $clientWideAccess = true;
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

                // Edge case: no device/sensor access -> skip client
                if (!$clientWideAccess && empty($allowedByDevice)) {
                    continue;
                }
            }

            foreach ($devices as $device) {
                $deviceId = $device->getId();

                foreach ([1, 2] as $entry) {
                    if ($restrictByAccess && !$clientWideAccess) {
                        $allowed = isset($allowedByDevice[$deviceId]) && in_array($entry, $allowedByDevice[$deviceId], true);
                        if (!$allowed) {
                            continue;
                        }
                    }

                    // Only used sensors
                    if (!($device->isTUsed($entry) || $device->isRhUsed($entry) || $device->isDUsed($entry))) {
                        continue;
                    }

                    $totalUsedSensors++;

                    $cache = $this->lastCacheRepository->findOneBy(['device' => $device, 'entry' => $entry]);
                    if (!$cache || !$cache->getDeviceDate()) {
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

                // Collect alarms; apply entry restrictions if any
                $alarmsCount = $this->deviceAlarmRepository->findNumberOfActiveAlarmsForDevice($device);
                if ($alarmsCount) {
                    $activeAlarms = $this->deviceAlarmRepository->findActiveAlarms($device);
                    foreach ($activeAlarms as $alarm) {
                        $sensor = $alarm->getSensor();
                        if ($restrictByAccess && !$clientWideAccess) {
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
                                $this->router->generate('app_alarm_list', [
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

            $data[$clientId] = [
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

        return $data;
    }

    /**
     * Returns offline alarm statistics for the dashboard.
     */
    public function getOfflineAlarmStats(): array
    {
        $todayStart = new \DateTime('today');
        $todayEnd = new \DateTime('tomorrow');
        $weekAgo = new \DateTime('-7 days');

        return [
            'active' => $this->deviceAlarmRepository->countActiveOfflineAlarms(),
            'total_today' => $this->deviceAlarmRepository->countOfflineAlarmsInRange($todayStart, $todayEnd),
            'total_week' => $this->deviceAlarmRepository->countOfflineAlarmsInRange($weekAgo),
        ];
    }
}
