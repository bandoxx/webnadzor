<?php

namespace App\Factory;

use App\Entity\Client;
use App\Entity\LoginLogArchive;

class LoginLogArchiveFactory
{

    public function create(Client $client, \DateTime $archiveDate, string $filename): LoginLogArchive
    {
        $archive = new LoginLogArchive();

        return $archive->setArchiveDate($archiveDate)
            ->setFilename($filename)
            ->setClient($client)
            ->setServerDate(new \DateTime())
        ;
    }

}