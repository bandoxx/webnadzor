<?php

namespace App\Service\Archiver\SIM;

use App\Entity\Client;
use App\Service\Archiver\ArchiverInterface;

interface SIMArchiverInterface extends ArchiverInterface
{
    public function generate(Client $client, array $data): void;
}