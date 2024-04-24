<?php

namespace App\Service\User;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class UserRemover
{

    public function __construct(private EntityManagerInterface $entityManager)
    {}

    public function remove(User $user): void
    {
        $this->removeLogs($user);
        $this->removeAccesses($user);

        $this->entityManager->remove($user);
        $this->entityManager->flush();
    }

    private function removeLogs(User $user): void
    {
        $logs = $user->getLoginLogs()->toArray();

        foreach ($logs as $log) {
            $this->entityManager->remove($log);
        }
    }

    private function removeAccesses(User $user): void
    {
        $accesses = $user->getUserDeviceAccesses()->toArray();

        foreach ($accesses as $access) {
            $this->entityManager->remove($access);
        }
    }
}