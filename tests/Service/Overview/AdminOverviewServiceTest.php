<?php

namespace App\Tests\Service\Overview;

use App\Entity\Client;
use App\Entity\Device;
use App\Entity\DeviceAlarm;
use App\Entity\User;
use App\Entity\UserDeviceAccess;
use App\Repository\AdminOverviewCacheRepository;
use App\Repository\ClientRepository;
use App\Repository\DeviceAlarmRepository;
use App\Repository\DeviceDataLastCacheRepository;
use App\Repository\DeviceRepository;
use App\Repository\UserDeviceAccessRepository;
use App\Service\Overview\AdminOverviewService;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class AdminOverviewServiceTest extends TestCase
{
    private ClientRepository&MockObject $clientRepository;
    private AdminOverviewCacheRepository&MockObject $cacheRepository;
    private DeviceRepository&MockObject $deviceRepository;
    private DeviceDataLastCacheRepository&MockObject $lastCacheRepository;
    private DeviceAlarmRepository&MockObject $deviceAlarmRepository;
    private UserDeviceAccessRepository&MockObject $userDeviceAccessRepository;
    private UrlGeneratorInterface&MockObject $router;
    private AdminOverviewService $service;

    protected function setUp(): void
    {
        $this->clientRepository = $this->createMock(ClientRepository::class);
        $this->cacheRepository = $this->createMock(AdminOverviewCacheRepository::class);
        $this->deviceRepository = $this->createMock(DeviceRepository::class);
        $this->lastCacheRepository = $this->createMock(DeviceDataLastCacheRepository::class);
        $this->deviceAlarmRepository = $this->createMock(DeviceAlarmRepository::class);
        $this->userDeviceAccessRepository = $this->createMock(UserDeviceAccessRepository::class);
        $this->router = $this->createMock(UrlGeneratorInterface::class);

        $this->service = new AdminOverviewService(
            $this->clientRepository,
            $this->cacheRepository,
            $this->deviceRepository,
            $this->lastCacheRepository,
            $this->deviceAlarmRepository,
            $this->userDeviceAccessRepository,
            $this->router
        );
    }

    public function testGetRedirectClientIdReturnsNullForRootUser(): void
    {
        $user = $this->createMockUser(User::ROLE_ROOT, []);

        $result = $this->service->getRedirectClientId($user);

        $this->assertNull($result);
    }

    public function testGetRedirectClientIdReturnsNullForMultipleClients(): void
    {
        $client1 = $this->createMockClient(1);
        $client2 = $this->createMockClient(2);
        $user = $this->createMockUser(User::ROLE_ADMINISTRATOR, [$client1, $client2]);

        $result = $this->service->getRedirectClientId($user);

        $this->assertNull($result);
    }

    public function testGetRedirectClientIdReturnsClientIdForSingleClient(): void
    {
        $client = $this->createMockClient(42);
        $user = $this->createMockUser(User::ROLE_ADMINISTRATOR, [$client]);

        $result = $this->service->getRedirectClientId($user);

        $this->assertEquals(42, $result);
    }

    public function testGetRedirectClientIdReturnsClientIdForRoleUserWithAccess(): void
    {
        $client = $this->createMockClient(42);
        $user = $this->createMockUser(User::ROLE_USER, [$client]);
        $access = $this->createMock(UserDeviceAccess::class);

        $this->userDeviceAccessRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['user' => $user, 'client' => $client])
            ->willReturn($access);

        $result = $this->service->getRedirectClientId($user);

        $this->assertEquals(42, $result);
    }

    public function testGetRedirectClientIdReturnsNullForRoleUserWithoutAccess(): void
    {
        $client = $this->createMockClient(42);
        $user = $this->createMockUser(User::ROLE_USER, [$client]);

        $this->userDeviceAccessRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->willReturn(null);

        $result = $this->service->getRedirectClientId($user);

        $this->assertNull($result);
    }

    public function testBuildOverviewReturnsEmptyArrayForModerator(): void
    {
        $client = $this->createMockClient(1);
        $user = $this->createMockUser(User::ROLE_ADMINISTRATOR, [], isModerator: true);

        $this->clientRepository->method('findAllActive')->willReturn([$client]);

        $result = $this->service->buildOverview($user);

        $this->assertEmpty($result);
    }

    public function testBuildOverviewReturnsEmptyArrayWhenUserHasNoClients(): void
    {
        $client = $this->createMockClient(1);
        $user = $this->createMockUser(User::ROLE_ADMINISTRATOR, []);

        $this->clientRepository->method('findAllActive')->willReturn([$client]);

        $result = $this->service->buildOverview($user);

        $this->assertEmpty($result);
    }

    public function testBuildOverviewUsesRootCacheData(): void
    {
        $client = $this->createMockClient(1, 'Test Client', 'Test Address', '12345678901');
        $user = $this->createMockUser(User::ROLE_ROOT, []);

        $cache = $this->createMock(\App\Entity\AdminOverviewCache::class);
        $cache->method('getNumberOfDevices')->willReturn(5);
        $cache->method('getOnlineDevices')->willReturn(3);
        $cache->method('getOfflineDevices')->willReturn(2);
        $cache->method('getAlarms')->willReturn(['Alarm 1', 'Alarm 2']);

        $this->clientRepository->method('findAllActive')->willReturn([$client]);
        $this->cacheRepository->method('findOneByClient')->willReturn($cache);

        $result = $this->service->buildOverview($user);

        $this->assertArrayHasKey(1, $result);
        $this->assertEquals(1, $result[1]['id']);
        $this->assertEquals('Test Client', $result[1]['name']);
        $this->assertEquals('Test Address', $result[1]['address']);
        $this->assertEquals('12345678901', $result[1]['oib']);
        $this->assertEquals(5, $result[1]['numberOfDevices']);
        $this->assertEquals(3, $result[1]['onlineDevices']);
        $this->assertEquals(2, $result[1]['offlineDevices']);
        $this->assertEquals(['Alarm 1', 'Alarm 2'], $result[1]['alarms']);
    }

    public function testBuildOverviewReturnsZeroesWhenNoCacheExists(): void
    {
        $client = $this->createMockClient(1);
        $user = $this->createMockUser(User::ROLE_ROOT, []);

        $this->clientRepository->method('findAllActive')->willReturn([$client]);
        $this->cacheRepository->method('findOneByClient')->willReturn(null);

        $result = $this->service->buildOverview($user);

        $this->assertEquals(0, $result[1]['numberOfDevices']);
        $this->assertEquals(0, $result[1]['onlineDevices']);
        $this->assertEquals(0, $result[1]['offlineDevices']);
        $this->assertEquals([], $result[1]['alarms']);
    }

    public function testBuildOverviewFiltersClientsByUserAccess(): void
    {
        $client1 = $this->createMockClient(1);
        $client2 = $this->createMockClient(2);
        $user = $this->createMockUser(User::ROLE_ADMINISTRATOR, [$client1]);

        $this->clientRepository->method('findAllActive')->willReturn([$client1, $client2]);
        $this->deviceRepository->method('findDevicesByClient')->willReturn([]);
        $this->userDeviceAccessRepository->method('findBy')->willReturn([]);

        $result = $this->service->buildOverview($user);

        $this->assertArrayHasKey(1, $result);
        $this->assertArrayNotHasKey(2, $result);
    }

    public function testBuildOverviewCountsSensorsCorrectly(): void
    {
        $client = $this->createMockClient(1);
        $user = $this->createMockUser(User::ROLE_ADMINISTRATOR, [$client]);

        $device = $this->createMockDevice(100, usedEntries: [1, 2]);
        $onlineCache = $this->createMockLastCache(time() - 60);
        $offlineCache = $this->createMockLastCache(time() - 3600);

        $this->clientRepository->method('findAllActive')->willReturn([$client]);
        $this->deviceRepository->method('findDevicesByClient')->willReturn([$device]);
        $this->lastCacheRepository->method('findOneBy')
            ->willReturnCallback(function ($criteria) use ($onlineCache, $offlineCache) {
                return $criteria['entry'] === 1 ? $onlineCache : $offlineCache;
            });
        $this->deviceAlarmRepository->method('findNumberOfActiveAlarmsForDevice')->willReturn(0);

        $result = $this->service->buildOverview($user);

        $this->assertEquals(2, $result[1]['numberOfDevices']);
        $this->assertEquals(1, $result[1]['onlineDevices']);
        $this->assertEquals(1, $result[1]['offlineDevices']);
    }

    public function testBuildOverviewReturnsNullForRoleUserWithNoAccess(): void
    {
        $client = $this->createMockClient(1);
        $user = $this->createMockUser(User::ROLE_USER, [$client]);

        $this->clientRepository->method('findAllActive')->willReturn([$client]);
        $this->userDeviceAccessRepository->method('findBy')->willReturn([]);

        $result = $this->service->buildOverview($user);

        $this->assertArrayNotHasKey(1, $result);
    }

    public function testBuildOverviewClientWideAccessForRoleUser(): void
    {
        $client = $this->createMockClient(1);
        $user = $this->createMockUser(User::ROLE_USER, [$client]);

        $access = $this->createMock(UserDeviceAccess::class);
        $access->method('getDevice')->willReturn(null);
        $access->method('getClient')->willReturn($client);

        $this->clientRepository->method('findAllActive')->willReturn([$client]);
        $this->userDeviceAccessRepository->method('findBy')->willReturn([$access]);
        $this->deviceRepository->method('findDevicesByClient')->willReturn([]);

        $result = $this->service->buildOverview($user);

        $this->assertArrayHasKey(1, $result);
    }

    public function testGetOfflineAlarmStatsReturnsCounts(): void
    {
        $this->deviceAlarmRepository
            ->method('countActiveOfflineAlarms')
            ->willReturn(5);
        $this->deviceAlarmRepository
            ->method('countOfflineAlarmsInRange')
            ->willReturnOnConsecutiveCalls(10, 50);

        $result = $this->service->getOfflineAlarmStats();

        $this->assertEquals(5, $result['active']);
        $this->assertEquals(10, $result['total_today']);
        $this->assertEquals(50, $result['total_week']);
    }

    private function createMockUser(int $permission, array $clients, bool $isModerator = false): User
    {
        $user = $this->createMock(User::class);
        $user->method('getPermission')->willReturn($permission);
        $user->method('getClients')->willReturn(new ArrayCollection($clients));
        $user->method('isModerator')->willReturn($isModerator);

        return $user;
    }

    private function createMockClient(
        int $id,
        string $name = 'Test',
        string $address = 'Address',
        string $oib = '00000000000'
    ): Client {
        $client = $this->createMock(Client::class);
        $client->method('getId')->willReturn($id);
        $client->method('getName')->willReturn($name);
        $client->method('getAddress')->willReturn($address);
        $client->method('getOIB')->willReturn($oib);
        $client->method('getOverviewViews')->willReturn(0);
        $client->method('getPdfLogo')->willReturn(null);
        $client->method('getMainLogo')->willReturn(null);
        $client->method('getMapMarkerIcon')->willReturn(null);
        $client->method('getDevicePageView')->willReturn(null);

        return $client;
    }

    private function createMockDevice(int $id, array $usedEntries = []): Device
    {
        $device = $this->createMock(Device::class);
        $device->method('getId')->willReturn($id);
        $device->method('isTUsed')->willReturnCallback(fn($e) => in_array($e, $usedEntries));
        $device->method('isRhUsed')->willReturn(false);
        $device->method('isDUsed')->willReturn(false);
        $device->method('getIntervalThresholdInSeconds')->willReturn(300);

        return $device;
    }

    private function createMockLastCache(int $timestamp): object
    {
        $cache = $this->createMock(\App\Entity\DeviceDataLastCache::class);
        $cache->method('getDeviceDate')->willReturn(new \DateTime("@$timestamp"));

        return $cache;
    }
}
