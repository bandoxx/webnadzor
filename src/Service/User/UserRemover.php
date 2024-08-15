<?php

namespace App\Service\User;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class UserRemover
{

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {}

    public function remove(User $user): void
    {
        $this->removeLogs($user);
        $this->removeAccesses($user);
        $this->removeClients($user);

        $this->entityManager->remove($user);
        $this->entityManager->flush();
    }

    private function removeClients(User $user): void
    {
        $clients = $user->getClients()->toArray();

        foreach ($clients as $client) {
            $user->removeClient($client);
        }

        $this->entityManager->flush();
    }

    private function removeLogs(User $user): void
    {
        $logs = $user->getLoginLogs()->toArray();

        foreach ($logs as $log) {
            $this->entityManager->remove($log);
        }

        $this->entityManager->flush();
    }

    private function removeAccesses(User $user): void
    {
        $accesses = $user->getUserDeviceAccesses()->toArray();

        foreach ($accesses as $access) {
            $this->entityManager->remove($access);
        }

        $this->entityManager->flush();
    }
}