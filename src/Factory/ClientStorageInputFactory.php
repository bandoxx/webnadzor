<?php

namespace App\Factory;

use App\Entity\ClientStorage;
use App\Entity\ClientStorageDevice;
use App\Entity\ClientStorageDigitalEntry;
use App\Entity\ClientStorageText;
use App\Entity\Device;

class ClientStorageInputFactory
{

    public function createText(ClientStorage $clientStorage, string $text, int $fontSize, string $color, $positionX, $positionY, bool $backgroundActive): ClientStorageText
    {
        return (new ClientStorageText())
            ->setClientStorage($clientStorage)
            ->setText($text)
            ->setFontColor($color)
            ->setFontSize($fontSize)
            ->setPositionX($positionX)
            ->setPositionY($positionY)
            ->setBackgroundActive($backgroundActive)
        ;
    }

    public function createDynamicText(ClientStorage $clientStorage, Device $device, int $entry, string $type, int $fontSize, string $color, $positionX, $positionY, bool $backgroundActive): ClientStorageDevice
    {
        return (new ClientStorageDevice())
            ->setClientStorage($clientStorage)
            ->setDevice($device)
            ->setEntry($entry)
            ->setType($type)
            ->setFontSize($fontSize)
            ->setFontColor($color)
            ->setPositionX($positionX)
            ->setPositionY($positionY)
            ->setBackgroundActive($backgroundActive)
        ;
    }

    public function createDigitalEntry(ClientStorage $clientStorage, Device $device, int $entry, int $fontSize, string $colorOn, string $colorOff, string $textOn, string $textOff, $positionX, $positionY, bool $backgroundActive): ClientStorageDigitalEntry
    {
        return (new ClientStorageDigitalEntry())
            ->setClientStorage($clientStorage)
            ->setDevice($device)
            ->setEntry($entry)
            ->setFontSize($fontSize)
            ->setFontColorOn($colorOn)
            ->setFontColorOff($colorOff)
            ->setTextOn($textOn)
            ->setTextOff($textOff)
            ->setPositionX($positionX)
            ->setPositionY($positionY)
            ->setBackgroundActive($backgroundActive)
        ;
    }


}