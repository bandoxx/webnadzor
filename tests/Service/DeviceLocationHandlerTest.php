<?php

namespace App\Tests\Service;

use App\Entity\Client;
use App\Entity\Device;
use App\Entity\User;
use App\Entity\UserDeviceAccess;
use App\Factory\DeviceOverviewFactory;
use App\Model\Device\DeviceOverviewModel;
use App\Repository\ClientRepository;
use App\Repository\DeviceRepository;
use App\Service\Device\UserAccess;
use App\Service\DeviceLocationHandler;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DeviceLocationHandlerTest extends TestCase
{
    private DeviceRepository&MockObject $deviceRepository;
    private UserAccess&MockObject $userAccess;
    private DeviceOverviewFactory&MockObject $deviceOverviewFactory;
    private ClientRepository&MockObject $clientRepository;
    private DeviceLocationHandler $handler;

    protected function setUp(): void
    {
        $this->deviceRepository = $this->createMock(DeviceRepository::class);
        $this->userAccess = $this->createMock(UserAccess::class);
        $this->deviceOverviewFactory = $this->createMock(DeviceOverviewFactory::class);
        $this->clientRepository = $this->createMock(ClientRepository::class);

        $this->handler = new DeviceLocationHandler(
            $this->deviceRepository,
            $this->userAccess,
            $this->deviceOverviewFactory,
            $this->clientRepository
        );
    }

    // ==================== getUserDeviceLocations Tests ====================

    public function testGetUserDeviceLocationsReturnsLocationsForAccessibleDevices(): void
    {
        $client = $this->createMockClient(1);
        $device = $this->createMockDevice(10, 'Device A', $client, [
            1 => ['t_use' => true, 't_name' => 'Location 1'],
            2 => ['t_use' => true, 't_name' => 'Location 2'],
        ]);

        $access1 = $this->createMockAccess($device, 1);
        $access2 = $this->createMockAccess($device, 2);

        $user = $this->createMock(User::class);
        $user->method('getUserDeviceAccesses')
            ->willReturn(new ArrayCollection([$access1, $access2]));

        $result = $this->handler->getUserDeviceLocations($user);

        $this->assertCount(2, $result);
        $this->assertArrayHasKey('10-1', $result);
        $this->assertArrayHasKey('10-2', $result);
        $this->assertEquals('Device A', $result['10-1']['name']);
        $this->assertEquals('Location 1', $result['10-1']['location']);
    }

    public function testGetUserDeviceLocationsSkipsUnusedEntries(): void
    {
        $client = $this->createMockClient(1);
        $device = $this->createMockDevice(10, 'Device A', $client, [
            1 => ['t_use' => false, 't_name' => 'Location 1'],
        ]);

        $access = $this->createMockAccess($device, 1);

        $user = $this->createMock(User::class);
        $user->method('getUserDeviceAccesses')
            ->willReturn(new ArrayCollection([$access]));

        $result = $this->handler->getUserDeviceLocations($user);

        $this->assertEmpty($result);
    }

    public function testGetUserDeviceLocationsReturnsEmptyForUserWithNoAccesses(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getUserDeviceAccesses')
            ->willReturn(new ArrayCollection([]));

        $result = $this->handler->getUserDeviceLocations($user);

        $this->assertEmpty($result);
    }

    // ==================== getClientDeviceLocations Tests ====================

    public function testGetClientDeviceLocationsReturnsLocationsForAllDevices(): void
    {
        $client = $this->createMockClient(1);
        $device1 = $this->createMockDevice(10, 'Device A', $client, [
            1 => ['t_use' => true, 't_name' => 'Loc A1'],
            2 => ['t_use' => true, 't_name' => 'Loc A2'],
        ]);
        $device2 = $this->createMockDevice(20, 'Device B', $client, [
            1 => ['t_use' => true, 't_name' => 'Loc B1'],
            2 => ['t_use' => false, 't_name' => 'Loc B2'],
        ]);

        $this->deviceRepository
            ->method('findDevicesByClient')
            ->with(1)
            ->willReturn([$device1, $device2]);

        $result = $this->handler->getClientDeviceLocations(1);

        $this->assertCount(3, $result);
        $this->assertArrayHasKey('10-1', $result);
        $this->assertArrayHasKey('10-2', $result);
        $this->assertArrayHasKey('20-1', $result);
        $this->assertArrayNotHasKey('20-2', $result);
    }

    public function testGetClientDeviceLocationsIncludesClientInKeyWhenRequested(): void
    {
        $client = $this->createMockClient(5);
        $device = $this->createMockDevice(10, 'Device A', $client, [
            1 => ['t_use' => true, 't_name' => 'Location'],
        ]);

        $this->deviceRepository
            ->method('findDevicesByClient')
            ->willReturn([$device]);

        $result = $this->handler->getClientDeviceLocations(5, true);

        $this->assertArrayHasKey('5-10-1', $result);
        $this->assertArrayNotHasKey('10-1', $result);
    }

    public function testGetClientDeviceLocationsReturnsFullNameFormat(): void
    {
        $client = $this->createMockClient(1);
        $device = $this->createMockDevice(10, 'Device A', $client, [
            1 => ['t_use' => true, 't_name' => 'Sensor Location'],
        ]);

        $this->deviceRepository
            ->method('findDevicesByClient')
            ->willReturn([$device]);

        $result = $this->handler->getClientDeviceLocations(1);

        $this->assertEquals('Device A', $result['10-1']['name']);
        $this->assertEquals('Sensor Location', $result['10-1']['location']);
        $this->assertEquals('Device A, Sensor Location', $result['10-1']['full']);
    }

    public function testGetClientDeviceLocationsReturnsEmptyForClientWithNoDevices(): void
    {
        $this->deviceRepository
            ->method('findDevicesByClient')
            ->willReturn([]);

        $result = $this->handler->getClientDeviceLocations(1);

        $this->assertEmpty($result);
    }

    // ==================== getAllClientDeviceLocations Tests ====================

    public function testGetAllClientDeviceLocationsMergesAllClients(): void
    {
        $client1 = $this->createMockClient(1);
        $client2 = $this->createMockClient(2);
        $device1 = $this->createMockDevice(10, 'Device A', $client1, [
            1 => ['t_use' => true, 't_name' => 'Loc 1'],
        ]);
        $device2 = $this->createMockDevice(20, 'Device B', $client2, [
            1 => ['t_use' => true, 't_name' => 'Loc 2'],
        ]);

        $this->clientRepository
            ->method('findBy')
            ->willReturn([$client1, $client2]);

        $this->deviceRepository
            ->method('findDevicesByClient')
            ->willReturnCallback(function ($clientId) use ($device1, $device2) {
                return $clientId === 1 ? [$device1] : [$device2];
            });

        $result = $this->handler->getAllClientDeviceLocations();

        $this->assertCount(2, $result);
        $this->assertArrayHasKey('1-10-1', $result);
        $this->assertArrayHasKey('2-20-1', $result);
    }

    public function testGetAllClientDeviceLocationsSkipsClientsWithNoData(): void
    {
        $client1 = $this->createMockClient(1);
        $client2 = $this->createMockClient(2);
        $device = $this->createMockDevice(10, 'Device A', $client1, [
            1 => ['t_use' => true, 't_name' => 'Loc'],
        ]);

        $this->clientRepository
            ->method('findBy')
            ->willReturn([$client1, $client2]);

        $this->deviceRepository
            ->method('findDevicesByClient')
            ->willReturnCallback(function ($clientId) use ($device) {
                return $clientId === 1 ? [$device] : [];
            });

        $result = $this->handler->getAllClientDeviceLocations();

        $this->assertCount(1, $result);
    }

    // ==================== getClientDeviceLocationData Tests ====================

    public function testGetClientDeviceLocationDataReturnsOverviewModels(): void
    {
        $client = $this->createMockClient(1);
        $user = $this->createMock(User::class);
        $device = $this->createMockDevice(10, 'Device A', $client);

        $entries = [
            ['device' => $device, 'entry' => 1],
            ['device' => $device, 'entry' => 2],
        ];

        $model1 = $this->createMock(DeviceOverviewModel::class);
        $model2 = $this->createMock(DeviceOverviewModel::class);

        $this->userAccess
            ->method('getAccessibleEntries')
            ->with($user, $client)
            ->willReturn($entries);

        $this->deviceOverviewFactory
            ->method('create')
            ->willReturnOnConsecutiveCalls($model1, $model2);

        $result = $this->handler->getClientDeviceLocationData($user, $client);

        $this->assertCount(2, $result);
        $this->assertSame($model1, $result[0]);
        $this->assertSame($model2, $result[1]);
    }

    public function testGetClientDeviceLocationDataSkipsNullOverviewModels(): void
    {
        $client = $this->createMockClient(1);
        $user = $this->createMock(User::class);
        $device = $this->createMockDevice(10, 'Device A', $client);

        $entries = [
            ['device' => $device, 'entry' => 1],
            ['device' => $device, 'entry' => 2],
        ];

        $model = $this->createMock(DeviceOverviewModel::class);

        $this->userAccess
            ->method('getAccessibleEntries')
            ->willReturn($entries);

        $this->deviceOverviewFactory
            ->method('create')
            ->willReturnOnConsecutiveCalls($model, null);

        $result = $this->handler->getClientDeviceLocationData($user, $client);

        $this->assertCount(1, $result);
    }

    public function testGetClientDeviceLocationDataReturnsEmptyForNoAccess(): void
    {
        $client = $this->createMockClient(1);
        $user = $this->createMock(User::class);

        $this->userAccess
            ->method('getAccessibleEntries')
            ->willReturn([]);

        $result = $this->handler->getClientDeviceLocationData($user, $client);

        $this->assertEmpty($result);
    }

    // ==================== Helper Methods ====================

    private function createMockClient(int $id): Client
    {
        $client = $this->createMock(Client::class);
        $client->method('getId')->willReturn($id);

        return $client;
    }

    private function createMockDevice(int $id, string $name, Client $client, array $entryData = []): Device
    {
        $device = $this->createMock(Device::class);
        $device->method('getId')->willReturn($id);
        $device->method('getName')->willReturn($name);
        $device->method('getClient')->willReturn($client);
        $device->method('getEntryData')->willReturnCallback(function ($entry) use ($entryData) {
            return $entryData[$entry] ?? ['t_use' => false, 't_name' => ''];
        });

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
