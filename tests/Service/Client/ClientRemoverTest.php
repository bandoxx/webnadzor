<?php

namespace App\Tests\Service\Client;

use App\Entity\Client;
use App\Entity\Device;
use App\Entity\User;
use App\Service\Client\ClientRemover;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ClientRemoverTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;
    private ClientRemover $clientRemover;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->clientRemover = new ClientRemover($this->entityManager);
    }

    public function testRemoveSoftDeletesClient(): void
    {
        $client = $this->createMockClient([]);
        $user = $this->createMock(User::class);

        $client->expects($this->once())
            ->method('setDeleted')
            ->with(true);

        $client->expects($this->once())
            ->method('setDeletedByUser')
            ->with($user);

        $client->expects($this->once())
            ->method('setDeletedAt')
            ->with($this->isInstanceOf(\DateTime::class));

        $this->clientRemover->remove($client, $user);
    }

    public function testRemoveSoftDeletesAllDevices(): void
    {
        $device1 = $this->createMock(Device::class);
        $device2 = $this->createMock(Device::class);
        $device3 = $this->createMock(Device::class);

        $client = $this->createMockClient([$device1, $device2, $device3]);
        $user = $this->createMock(User::class);

        $device1->expects($this->once())->method('setDeleted')->with(true);
        $device2->expects($this->once())->method('setDeleted')->with(true);
        $device3->expects($this->once())->method('setDeleted')->with(true);

        $this->clientRemover->remove($client, $user);
    }

    public function testRemoveFlushesChanges(): void
    {
        $client = $this->createMockClient([]);
        $user = $this->createMock(User::class);

        $this->entityManager
            ->expects($this->exactly(2))
            ->method('flush');

        $this->clientRemover->remove($client, $user);
    }

    public function testRemoveWithNoDevices(): void
    {
        $client = $this->createMockClient([]);
        $user = $this->createMock(User::class);

        $client->expects($this->once())->method('setDeleted')->with(true);

        $this->entityManager
            ->expects($this->exactly(2))
            ->method('flush');

        $this->clientRemover->remove($client, $user);
    }

    public function testRemoveSetsCorrectDeletedAtTimestamp(): void
    {
        $client = $this->createMockClient([]);
        $user = $this->createMock(User::class);

        $capturedDateTime = null;
        $client->expects($this->once())
            ->method('setDeletedAt')
            ->willReturnCallback(function (\DateTime $dateTime) use (&$capturedDateTime, $client) {
                $capturedDateTime = $dateTime;
                return $client;
            });

        $beforeRemove = new \DateTime();
        $this->clientRemover->remove($client, $user);
        $afterRemove = new \DateTime();

        $this->assertNotNull($capturedDateTime);
        $this->assertGreaterThanOrEqual($beforeRemove->getTimestamp(), $capturedDateTime->getTimestamp());
        $this->assertLessThanOrEqual($afterRemove->getTimestamp(), $capturedDateTime->getTimestamp());
    }

    public function testRemovePreservesDeviceOrder(): void
    {
        $device1 = $this->createMock(Device::class);
        $device2 = $this->createMock(Device::class);

        $client = $this->createMockClient([$device1, $device2]);
        $user = $this->createMock(User::class);

        $deletedOrder = [];
        $device1->expects($this->once())
            ->method('setDeleted')
            ->willReturnCallback(function () use (&$deletedOrder, $device1) {
                $deletedOrder[] = 1;
                return $device1;
            });
        $device2->expects($this->once())
            ->method('setDeleted')
            ->willReturnCallback(function () use (&$deletedOrder, $device2) {
                $deletedOrder[] = 2;
                return $device2;
            });

        $this->clientRemover->remove($client, $user);

        $this->assertEquals([1, 2], $deletedOrder);
    }

    private function createMockClient(array $devices): Client&MockObject
    {
        $client = $this->createMock(Client::class);
        $client->method('getDevice')->willReturn(new ArrayCollection($devices));

        return $client;
    }
}
