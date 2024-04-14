<?php

namespace App\Twig;

use App\Repository\ClientRepository;

class GlobalVariables
{

    public function __construct(private ClientRepository $clientRepository)
    {

    }

    public function getClient($clientId)
    {
        return $this->clientRepository->find($clientId);
    }

}