<?php

namespace App\Twig;

use App\Repository\ClientRepository;

class GlobalVariables
{

    public function __construct(private ClientRepository $clientRepository, private $mapMarkerDirectory)
    {

    }

    public function getClient($clientId)
    {
        return $this->clientRepository->find($clientId);
    }

    public function getMapMarkerDirectory()
    {
        return $this->mapMarkerDirectory;
    }

}