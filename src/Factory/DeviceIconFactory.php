<?php

namespace App\Factory;

use App\Entity\DeviceIcon;

class DeviceIconFactory
{

    public function create(string $title, string $fileName): DeviceIcon
    {
        return (new DeviceIcon())
            ->setTitle($title)
            ->setFilename($fileName)
        ;
    }

}