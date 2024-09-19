<?php

namespace App\Factory;

use App\Entity\DeviceIcon;

class DeviceIconFactory
{

    public function create(string $fileName, string $title): DeviceIcon
    {
        return (new DeviceIcon())
            ->setTitle($title)
            ->setFilename($fileName)
        ;
    }

}