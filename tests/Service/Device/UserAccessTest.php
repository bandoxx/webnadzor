<?php

namespace App\Tests\Service\Device;

use App\Entity\Client;
use App\Entity\Device;
use App\Entity\User;
use App\Entity\UserDeviceAccess;
use App\Repository\DeviceRepository;
use App\Repository\UserDeviceAccessRepository;
use App\Service\Device\UserAccess;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UserAccessTest extends TestCase
{
    private DeviceRepository&MockObject $deviceRepository;
    private UserDeviceAccessRepository&MockObject $deviceAccessRepository;
    private UserAccess $userAccess;

    protected function setUp(): void
    {
        $this->deviceRepository = $this->createMock(DeviceRepository::class);
        $this->deviceAccessRepository = $this->createMock(UserDeviceAccessRepository::class);

        $this->userAccess = new UserAccess(
            $this->deviceRepository,
            $this->deviceAccessRepository
        );
    }

    // ==================== getAccessibleDevices Tests ====================

    public function testGetAccessibleDevicesReturnsAllDevicesForModerator(): void
    {
        $client = $this->createMockClient(1);
        $user = $this->createMockUser(User::ROLE_MODERATOR);
        $device1 = $this->createMockDevice(1);
        $device2 = $this->createMockDevice(2);

        $this->deviceRepository
            ->expects($this->once())
            ->method('findDevicesByClient')
            ->with(1)
            ->willReturn([$device1, $device2]);

        $result = $this->userAccess->getAccessibleDevices($client, $user);

        $this->assertCount(2, $result);
        $this->assertContains($device1, $result);
        $this->assertContains($device2, $result);
    }

    public function testGetAccessibleDevicesReturnsAllDevicesForRoot(): void
    {
        $client = $this->createMockClient(1);
        $user = $this->createMockUser(User::ROLE_ROOT);
        $device = $this->createMockDevice(1);

        $this->deviceRepository
            ->expects($this->once())
            ->method('findDevicesByClient')
            ->willReturn([$device]);

        $result = $this->userAccess->getAccessibleDevices($client, $user);

        $this->assertCount(1, $result);
    }

    public function testGetAccessibleDevicesReturnsOnlyAccessibleDevicesForUser(): void
    {
        $client = $this->createMockClient(1);
        $user = $this->createMockUser(User::ROLE_USER);
        $device1 = $this->createMockDevice(1);
        $device2 = $this->createMockDevice(2);

        $access1 = $this->createMockAccess($device1, 1);
        $access2 = $this->createMockAccess($device2, 1);

        $this->deviceAccessRepository
            ->expects($this->once())
            ->method('findAccessibleEntries')
            ->with($user)
            ->willReturn([$access1, $access2]);

        $result = $this->userAccess->getAccessibleDevices($client, $user);

        $this->assertCount(2, $result);
    }

    public function testGetAccessibleDevicesDeduplicatesDevices(): void
    {
        $client = $this->createMockClient(1);
        $user = $this->createMockUser(User::ROLE_USER);
        $device = $this->createMockDevice(1);

        // Same device, different sensors
        $access1 = $this->createMockAccess($device, 1);
        $access2 = $this->createMockAccess($device, 2);

        $this->deviceAccessRepository
            ->method('findAccessibleEntries')
            ->willReturn([$access1, $access2]);

        $result = $this->userAccess->getAccessibleDevices($client, $user);

        $this->assertCount(1, $result);
    }

    public function testGetAccessibleDevicesReturnsEmptyForUserWithNoAccess(): void
    {
        $client = $this->createMockClient(1);
        $user = $this->createMockUser(User::ROLE_USER);

        $this->deviceAccessRepository
            ->method('findAccessibleEntries')
            ->willReturn([]);

        $result = $this->userAccess->getAccessibleDevices($client, $user);

        $this->assertEmpty($result);
    }

    // ==================== getAccessibleEntries Tests ====================

    public function testGetAccessibleEntriesReturnsAllUsedEntriesForModerator(): void
    {
        $client = $this->createMockClient(1);
        $user = $this->createMockUser(User::ROLE_MODERATOR);
        $device = $this->createMockDevice(1, [1 => true, 2 => true]);

        $this->deviceRepository
            ->method('findDevicesByClient')
            ->willReturn([$device]);

        $result = $this->userAccess->getAccessibleEntries($user, $client);

        $this->assertCount(2, $result);
        $this->assertEquals(1, $result[0]['entry']);
        $this->assertEquals(2, $result[1]['entry']);
    }

    public function testGetAccessibleEntriesSkipsUnusedEntries(): void
    {
        $client = $this->createMockClient(1);
        $user = $this->createMockUser(User::ROLE_MODERATOR);
        $device = $this->createMockDevice(1, [1 => true, 2 => false]);

        $this->deviceRepository
            ->method('findDevicesByClient')
            ->willReturn([$device]);

        $result = $this->userAccess->getAccessibleEntries($user, $client);

        $this->assertCount(1, $result);
        $this->assertEquals(1, $result[0]['entry']);
    }

    public function testGetAccessibleEntriesReturnsAllEntriesForClientWideAccess(): void
    {
        $client = $this->createMockClient(1);
        $user = $this->createMockUser(User::ROLE_USER);
        $device = $this->createMockDevice(1, [1 => true, 2 => true]);

        // Client-wide access (device is null, client is set)
        $clientWideAccess = $this->createMock(UserDeviceAccess::class);
        $clientWideAccess->method('getClient')->willReturn($client);
        $clientWideAccess->method('getDevice')->willReturn(null);

        $this->deviceAccessRepository
            ->method('findBy')
            ->willReturn([$clientWideAccess]);

        $this->deviceRepository
            ->method('findDevicesByClient')
            ->willReturn([$device]);

        $result = $this->userAccess->getAccessibleEntries($user, $client);

        $this->assertCount(2, $result);
    }

    public function testGetAccessibleEntriesReturnsOnlyAllowedEntriesForDeviceAccess(): void
    {
        $client = $this->createMockClient(1);
        $user = $this->createMockUser(User::ROLE_USER);
        $device = $this->createMockDevice(1, [1 => true, 2 => true], $client);

        // Device-specific access to sensor 1 only
        $deviceAccess = $this->createMock(UserDeviceAccess::class);
        $deviceAccess->method('getClient')->willReturn(null);
        $deviceAccess->method('getDevice')->willReturn($device);
        $deviceAccess->method('getSensor')->willReturn(1);

        $this->deviceAccessRepository
            ->method('findBy')
            ->willReturn([$deviceAccess]);

        $result = $this->userAccess->getAccessibleEntries($user, $client);

        $this->assertCount(1, $result);
        $this->assertEquals(1, $result[0]['entry']);
        $this->assertSame($device, $result[0]['device']);
    }

    public function testGetAccessibleEntriesSkipsInvalidSensors(): void
    {
        $client = $this->createMockClient(1);
        $user = $this->createMockUser(User::ROLE_USER);
        $device = $this->createMockDevice(1, [1 => true, 2 => true], $client);

        // Access with invalid sensor (0)
        $invalidAccess = $this->createMock(UserDeviceAccess::class);
        $invalidAccess->method('getClient')->willReturn(null);
        $invalidAccess->method('getDevice')->willReturn($device);
        $invalidAccess->method('getSensor')->willReturn(0);

        // Access with invalid sensor (3)
        $invalidAccess2 = $this->createMock(UserDeviceAccess::class);
        $invalidAccess2->method('getClient')->willReturn(null);
        $invalidAccess2->method('getDevice')->willReturn($device);
        $invalidAccess2->method('getSensor')->willReturn(3);

        $this->deviceAccessRepository
            ->method('findBy')
            ->willReturn([$invalidAccess, $invalidAccess2]);

        $result = $this->userAccess->getAccessibleEntries($user, $client);

        $this->assertEmpty($result);
    }

    public function testGetAccessibleEntriesSkipsDevicesFromOtherClients(): void
    {
        $client1 = $this->createMockClient(1);
        $client2 = $this->createMockClient(2);
        $user = $this->createMockUser(User::ROLE_USER);
        $deviceFromOtherClient = $this->createMockDevice(1, [1 => true], $client2);

        $deviceAccess = $this->createMock(UserDeviceAccess::class);
        $deviceAccess->method('getClient')->willReturn(null);
        $deviceAccess->method('getDevice')->willReturn($deviceFromOtherClient);
        $deviceAccess->method('getSensor')->willReturn(1);

        $this->deviceAccessRepository
            ->method('findBy')
            ->willReturn([$deviceAccess]);

        $result = $this->userAccess->getAccessibleEntries($user, $client1);

        $this->assertEmpty($result);
    }

    public function testGetAccessibleEntriesSkipsNullDeviceInDeviceSpecificMode(): void
    {
        $client = $this->createMockClient(1);
        $user = $this->createMockUser(User::ROLE_USER);

        // Access with null device (not client-wide since client is also null)
        $nullDeviceAccess = $this->createMock(UserDeviceAccess::class);
        $nullDeviceAccess->method('getClient')->willReturn(null);
        $nullDeviceAccess->method('getDevice')->willReturn(null);

        $this->deviceAccessRepository
            ->method('findBy')
            ->willReturn([$nullDeviceAccess]);

        $result = $this->userAccess->getAccessibleEntries($user, $client);

        $this->assertEmpty($result);
    }

    // ==================== Helper Methods ====================

    private function createMockUser(int $permission): User
    {
        $user = $this->createMock(User::class);
        $user->method('getPermission')->willReturn($permission);

        return $user;
    }

    private function createMockClient(int $id): Client
    {
        $client = $this->createMock(Client::class);
        $client->method('getId')->willReturn($id);

        return $client;
    }

    private function createMockDevice(int $id, array $usedEntries = [], ?Client $client = null): Device
    {
        $device = $this->createMock(Device::class);
        $device->method('getId')->willReturn($id);
        $device->method('getEntryData')->willReturnCallback(function ($entry) use ($usedEntries) {
            return ['t_use' => $usedEntries[$entry] ?? false];
        });

        if ($client !== null) {
            $device->method('getClient')->willReturn($client);
        }

        return $device;
    }

    private function createMockAccess(Device $device, int $sensor): UserDeviceAccess
    {
        $access = $this->createMock(UserDeviceAccess::class);
        $access->method('getDevice')->willReturn($device);
        $access->method('getSensor')->willReturn($sensor);

        return $access;
    }
}
