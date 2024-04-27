<?php

namespace App\Twig;

use App\Entity\Client;
use App\Repository\ClientRepository;

class GlobalVariables
{

    public function __construct(
        private readonly ClientRepository $clientRepository,
        private readonly string $mapMarkerDirectory
    ) {}

    public function getClient(int $clientId): ?Client
    {
        return $this->clientRepository->find($clientId);
    }

    public function getMapMarkerDirectory(): string
    {
        return $this->mapMarkerDirectory;
    }

}