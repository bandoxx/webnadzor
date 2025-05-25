<?php

namespace App\Factory;

use App\Entity\UnresolvedDeviceData;

class UnresolvedDeviceDataFactory
{
    public function createFromJson(string $content): UnresolvedDeviceData
    {
        return $this->create($content);
    }

    public function createFromXml(string $xmlPath): UnresolvedDeviceData
    {
        return $this->create(file_get_contents($xmlPath), basename($xmlPath));
    }

    private function create(string $content, ?string $xmlName = null): UnresolvedDeviceData
    {
        return (new UnresolvedDeviceData())
            ->setContent($content)
            ->setXmlName($xmlName)
            ->setCreatedAt(new \DateTimeImmutable())
        ;
    }

}