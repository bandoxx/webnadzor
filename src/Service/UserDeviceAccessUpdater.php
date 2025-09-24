<?php

namespace App\Service;

use App\Entity\User;
use App\Factory\UserDeviceAccessFactory;
use App\Repository\ClientRepository;
use App\Repository\DeviceRepository;
use Doctrine\ORM\EntityManagerInterface;

class UserDeviceAccessUpdater
{

    public function __construct(
        private readonly DeviceRepository        $deviceRepository,
        private readonly UserDeviceAccessFactory $userDeviceAccessFactory,
        private readonly EntityManagerInterface  $entityManager,
        private readonly ClientRepository        $clientRepository,
        private array                            $devices = []
    ) {}

    public function update(User $user, ?array $clients, ?array $locations): void
    {
        foreach ($user->getUserDeviceAccesses()->toArray() as $access) {
            $this->entityManager->remove($access);
        }

        foreach ($user->getClients() as $client) {
            $user->removeClient($client);
        }

        $this->entityManager->flush();

        if ($user->getPermission() === 1) {
            foreach ($locations as $location) {
                [$clientId, $deviceId, $entry] = explode('-', $location);

                $clientId = (int) $clientId;
                $deviceId = (int) $deviceId;
                $entry    = (int) $entry;

                if (!$clientId) {
                    continue;
                }

                if (array_key_exists($deviceId, $this->devices) === false) {
                    $this->devices[$deviceId] = $this->deviceRepository->find($deviceId);
                }

                $userDeviceAccess = $this->userDeviceAccessFactory->create($this->devices[$deviceId], $user, $entry);

                $this->entityManager->persist($userDeviceAccess);
                $user->addUserDeviceAccess($userDeviceAccess);
            }

            foreach ($clients as $clientId) {
                $this->assignClients($user, $clientId);
            }
        }

        if (in_array($user->getPermission(), [2, 3], true)) {
            foreach ($clients as $clientId) {
                $this->assignClients($user, $clientId);
            }
        }

        $this->entityManager->flush();
    }

    private function assignClients(User $user, int $clientId): void
    {
        $client = $this->clientRepository->find($clientId);

        $user->addClient($client);
    }
}