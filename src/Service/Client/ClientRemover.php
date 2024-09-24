<?php

namespace App\Service\Client;

use App\Entity\Client;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class ClientRemover
{

    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {}

    public function remove(Client $client, User $removedByUser): void
    {
        $client->setDeleted(true);
        $client->setDeletedByUser($removedByUser);
        $client->setDeletedAt(new \DateTime());

        $this->entityManager->flush();

        $devices = $client->getDevice()->toArray();

        foreach ($devices as $device) {
            $device->setDeleted(true);
        }

        $this->entityManager->flush();
    }
}