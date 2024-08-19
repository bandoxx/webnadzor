<?php

namespace App\Factory;

use App\Entity\Client;
use App\Entity\ClientFtp;

class ClientFtpFactory
{

    public function create(Client $client): ClientFtp
    {
        return (new ClientFtp())
            ->setClient($client)
            ->setUsername('')
            ->setPassword('')
            ->setHost('')
        ;
    }

}