<?php

namespace App\Tests\Service\User;

use App\Entity\Client;
use App\Entity\LoginLog;
use App\Entity\User;
use App\Entity\UserDeviceAccess;
use App\Service\User\UserRemover;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UserRemoverTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;
    private UserRemover $userRemover;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->userRemover = new UserRemover($this->entityManager);
    }

    public function testRemoveDeletesUserWithNoRelations(): void
    {
        $user = $this->createMockUser([], [], []);

        $this->entityManager
            ->expects($this->once())
            ->method('remove')
            ->with($user);

        $this->entityManager
            ->expects($this->exactly(4))
            ->method('flush');

        $this->userRemover->remove($user);
    }

    public function testRemoveDeletesLoginLogs(): void
    {
        $log1 = $this->createMock(LoginLog::class);
        $log2 = $this->createMock(LoginLog::class);
        $user = $this->createMockUser([$log1, $log2], [], []);

        $removedEntities = [];
        $this->entityManager
            ->expects($this->exactly(3))
            ->method('remove')
            ->willReturnCallback(function ($entity) use (&$removedEntities) {
                $removedEntities[] = $entity;
            });

        $this->userRemover->remove($user);

        $this->assertContains($log1, $removedEntities);
        $this->assertContains($log2, $removedEntities);
        $this->assertContains($user, $removedEntities);
    }

    public function testRemoveDeletesDeviceAccesses(): void
    {
        $access1 = $this->createMock(UserDeviceAccess::class);
        $access2 = $this->createMock(UserDeviceAccess::class);
        $user = $this->createMockUser([], [$access1, $access2], []);

        $removedEntities = [];
        $this->entityManager
            ->expects($this->exactly(3))
            ->method('remove')
            ->willReturnCallback(function ($entity) use (&$removedEntities) {
                $removedEntities[] = $entity;
            });

        $this->userRemover->remove($user);

        $this->assertContains($access1, $removedEntities);
        $this->assertContains($access2, $removedEntities);
    }

    public function testRemoveDisassociatesClients(): void
    {
        $client1 = $this->createMock(Client::class);
        $client2 = $this->createMock(Client::class);
        $user = $this->createMockUser([], [], [$client1, $client2]);

        $removedClients = [];
        $user->expects($this->exactly(2))
            ->method('removeClient')
            ->willReturnCallback(function ($client) use (&$removedClients, $user) {
                $removedClients[] = $client;
                return $user;
            });

        $this->userRemover->remove($user);

        $this->assertContains($client1, $removedClients);
        $this->assertContains($client2, $removedClients);
    }

    public function testRemoveHandlesAllRelationsTogether(): void
    {
        $log = $this->createMock(LoginLog::class);
        $access = $this->createMock(UserDeviceAccess::class);
        $client = $this->createMock(Client::class);
        $user = $this->createMockUser([$log], [$access], [$client]);

        $this->entityManager
            ->expects($this->exactly(3))
            ->method('remove');

        $user->expects($this->once())
            ->method('removeClient')
            ->willReturn($user);

        $this->entityManager
            ->expects($this->exactly(4))
            ->method('flush');

        $this->userRemover->remove($user);
    }

    public function testRemoveFlushesAfterEachStep(): void
    {
        $user = $this->createMockUser([], [], []);

        $flushCount = 0;
        $this->entityManager
            ->expects($this->exactly(4))
            ->method('flush')
            ->willReturnCallback(function () use (&$flushCount) {
                $flushCount++;
            });

        $this->userRemover->remove($user);

        $this->assertEquals(4, $flushCount);
    }

    private function createMockUser(array $logs, array $accesses, array $clients): User&MockObject
    {
        $user = $this->createMock(User::class);
        $user->method('getLoginLogs')->willReturn(new ArrayCollection($logs));
        $user->method('getUserDeviceAccesses')->willReturn(new ArrayCollection($accesses));
        $user->method('getClients')->willReturn(new ArrayCollection($clients));

        return $user;
    }
}
