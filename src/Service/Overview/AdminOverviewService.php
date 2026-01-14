<?php

namespace App\Service\Overview;

use App\Entity\Client;
use App\Entity\Device;
use App\Entity\DeviceAlarm;
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
        if ($user->getPermission() === User::ROLE_ROOT) {
            return null;
        }

        if ($user->getClients()->count() !== 1) {
            return null;
        }

        $singleClient = $user->getClients()->first();
        $clientId = $singleClient->getId();

        if ($user->getPermission() === User::ROLE_USER) {
            $hasAccess = (bool) $this->userDeviceAccessRepository->findOneBy([
                'user' => $user,
                'client' => $singleClient,
            ]);
            return $hasAccess ? $clientId : null;
        }

        return $clientId;
    }

    /**
     * Build admin overview clients data for the given user.
     */
    public function buildOverview(User $user): array
    {
        $data = [];
        $clients = $this->clientRepository->findAllActive();

        foreach ($clients as $client) {
            if (!$this->canUserAccessClient($user, $client)) {
                continue;
            }

            $clientData = $this->buildClientOverviewData($user, $client);
            if ($clientData !== null) {
                $data[$client->getId()] = $clientData;
            }
        }

        return $data;
    }

    private function canUserAccessClient(User $user, Client $client): bool
    {
        if ($user->isModerator()) {
            return false;
        }

        if ($user->getPermission() === User::ROLE_ROOT) {
            return true;
        }

        return $user->getClients()->contains($client);
    }

    private function buildClientOverviewData(User $user, Client $client): ?array
    {
        if ($user->getPermission() === User::ROLE_ROOT) {
            return $this->buildClientDataFromCache($client);
        }

        return $this->buildClientDataLive($user, $client);
    }

    private function buildClientDataFromCache(Client $client): array
    {
        $cache = $this->cacheRepository->findOneByClient($client);

        return $this->buildClientData(
            $client,
            $cache?->getNumberOfDevices() ?? 0,
            $cache?->getOnlineDevices() ?? 0,
            $cache?->getOfflineDevices() ?? 0,
            $cache?->getAlarms() ?? []
        );
    }

    private function buildClientDataLive(User $user, Client $client): ?array
    {
        $accessFilter = $this->buildUserAccessFilter($user, $client);

        if ($accessFilter === null) {
            return null;
        }

        $devices = $this->deviceRepository->findDevicesByClient($client->getId());
        $sensorStats = $this->countSensorStatus($devices, $accessFilter);
        $alarmMessages = $this->collectAlarmMessages($devices, $client->getId(), $accessFilter);

        return $this->buildClientData(
            $client,
            $sensorStats['total'],
            $sensorStats['online'],
            $sensorStats['offline'],
            $alarmMessages
        );
    }

    private function buildClientData(
        Client $client,
        int $numberOfDevices,
        int $onlineDevices,
        int $offlineDevices,
        array $alarms
    ): array {
        return [
            'id' => $client->getId(),
            'name' => $client->getName(),
            'address' => $client->getAddress(),
            'oib' => $client->getOIB(),
            'numberOfDevices' => $numberOfDevices,
            'onlineDevices' => $onlineDevices,
            'offlineDevices' => $offlineDevices,
            'overview' => $client->getOverviewViews(),
            'pdfLogo' => $client->getPdfLogo(),
            'mainLogo' => $client->getMainLogo(),
            'mapIcon' => $client->getMapMarkerIcon(),
            'devicePageView' => $client->getDevicePageView(),
            'alarms' => $alarms,
        ];
    }

    /**
     * Build access filter for user. Returns null if user has no access.
     * @return array{clientWide: bool, byDevice: array<int, int[]>}|null
     */
    private function buildUserAccessFilter(User $user, Client $client): ?array
    {
        if ($user->getPermission() !== User::ROLE_USER) {
            return ['clientWide' => true, 'byDevice' => []];
        }

        $accessList = $this->userDeviceAccessRepository->findBy([
            'user' => $user,
            'client' => $client,
        ]);

        $clientWide = false;
        $byDevice = [];

        foreach ($accessList as $access) {
            $device = $access->getDevice();
            $sensor = $access->getSensor();

            if (!$device && $access->getClient()) {
                $clientWide = true;
                continue;
            }

            if ($device) {
                $deviceId = $device->getId();
                if (!isset($byDevice[$deviceId])) {
                    $byDevice[$deviceId] = [];
                }

                if ($sensor === null) {
                    $byDevice[$deviceId] = [1, 2];
                } elseif (!in_array($sensor, $byDevice[$deviceId], true)) {
                    $byDevice[$deviceId][] = (int) $sensor;
                }
            }
        }

        if (!$clientWide && empty($byDevice)) {
            return null;
        }

        return ['clientWide' => $clientWide, 'byDevice' => $byDevice];
    }

    private function isEntryAllowed(int $deviceId, int $entry, array $accessFilter): bool
    {
        if ($accessFilter['clientWide']) {
            return true;
        }

        return isset($accessFilter['byDevice'][$deviceId])
            && in_array($entry, $accessFilter['byDevice'][$deviceId], true);
    }

    private function isAlarmAllowed(DeviceAlarm $alarm, int $deviceId, array $accessFilter): bool
    {
        if ($accessFilter['clientWide']) {
            return true;
        }

        $sensor = $alarm->getSensor();

        if ($sensor !== null) {
            return isset($accessFilter['byDevice'][$deviceId])
                && in_array((int) $sensor, $accessFilter['byDevice'][$deviceId], true);
        }

        return isset($accessFilter['byDevice'][$deviceId]);
    }

    /**
     * @return array{total: int, online: int, offline: int}
     */
    private function countSensorStatus(array $devices, array $accessFilter): array
    {
        $total = 0;
        $online = 0;
        $offline = 0;

        foreach ($devices as $device) {
            $deviceId = $device->getId();

            foreach ([1, 2] as $entry) {
                if (!$this->isEntryAllowed($deviceId, $entry, $accessFilter)) {
                    continue;
                }

                if (!$this->isSensorUsed($device, $entry)) {
                    continue;
                }

                $total++;

                if ($this->isSensorOnline($device, $entry)) {
                    $online++;
                } else {
                    $offline++;
                }
            }
        }

        return ['total' => $total, 'online' => $online, 'offline' => $offline];
    }

    private function isSensorUsed(Device $device, int $entry): bool
    {
        return $device->isTUsed($entry) || $device->isRhUsed($entry) || $device->isDUsed($entry);
    }

    private function isSensorOnline(Device $device, int $entry): bool
    {
        $cache = $this->lastCacheRepository->findOneBy([
            'device' => $device,
            'entry' => $entry,
        ]);

        if (!$cache || !$cache->getDeviceDate()) {
            return false;
        }

        $secondsSinceLastData = time() - $cache->getDeviceDate()->format('U');

        return $secondsSinceLastData < $device->getIntervalTrashholdInSeconds();
    }

    private function collectAlarmMessages(array $devices, int $clientId, array $accessFilter): array
    {
        $messages = [];

        foreach ($devices as $device) {
            $deviceId = $device->getId();
            $alarmsCount = $this->deviceAlarmRepository->findNumberOfActiveAlarmsForDevice($device);

            if (!$alarmsCount) {
                continue;
            }

            $activeAlarms = $this->deviceAlarmRepository->findActiveAlarms($device);

            foreach ($activeAlarms as $alarm) {
                if (!$this->isAlarmAllowed($alarm, $deviceId, $accessFilter)) {
                    continue;
                }

                $messages[] = $this->formatAlarmMessage($alarm, $clientId, $deviceId);
            }
        }

        return $messages;
    }

    private function formatAlarmMessage(DeviceAlarm $alarm, int $clientId, int $deviceId): string
    {
        $sensor = $alarm->getSensor();
        $message = $alarm->getMessage() ?? '';
        $timeString = $alarm->getTimeString();

        if (!$sensor) {
            return trim($message . ' ' . $timeString);
        }

        $link = sprintf(
            "<a href='%s'><b><u>Link do alarma</u></b></a>",
            $this->router->generate('app_alarm_list', [
                'clientId' => $clientId,
                'id' => $deviceId,
                'entry' => $sensor,
            ], UrlGeneratorInterface::ABSOLUTE_PATH)
        );

        return sprintf('%s %s - %s', $message, $timeString, $link);
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
