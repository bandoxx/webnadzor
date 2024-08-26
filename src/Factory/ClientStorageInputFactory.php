<?php

namespace App\Factory;

use App\Entity\ClientStorage;
use App\Entity\ClientStorageDevice;
use App\Entity\ClientStorageText;
use App\Entity\Device;

class ClientStorageInputFactory
{

    public function createText(ClientStorage $clientStorage, string $text, string $color, $positionX, $positionY): ClientStorageText
    {
        return (new ClientStorageText())
            ->setClientStorage($clientStorage)
            ->setText($text)
            ->setFontColor($color)
            ->setFontSize(0)
            ->setPositionX($positionX)
            ->setPositionY($positionY)
        ;
    }

    public function createDynamicText(ClientStorage $clientStorage, Device $device, int $entry, string $type, string $color, $positionX, $positionY): ClientStorageDevice
    {
        return (new ClientStorageDevice())
            ->setClientStorage($clientStorage)
            ->setDevice($device)
            ->setEntry($entry)
            ->setType($type)
            ->setFontSize(0)
            ->setFontColor($color)
            ->setPositionX($positionX)
            ->setPositionY($positionY)
        ;
    }
}