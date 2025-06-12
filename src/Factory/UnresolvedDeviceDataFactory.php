<?php

namespace App\Factory;

use App\Entity\UnresolvedDeviceData;

class UnresolvedDeviceDataFactory
{
    public function createFromString(string $data): UnresolvedDeviceData
    {
        return $this->create($data);
    }

    public function createFromArray(array $content): UnresolvedDeviceData
    {
        return $this->create(json_encode($content));
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