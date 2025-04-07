<?php

namespace App\Factory;

use App\Entity\UnresolvedXML;

class UnresolvedXMLFactory
{

    public function create(string $xmlPath): UnresolvedXML
    {
        $unresolvedXML = new UnresolvedXML();
        $unresolvedXML->setXmlName(basename($xmlPath));
        $unresolvedXML->setContent(file_get_contents($xmlPath));
        $unresolvedXML->setCreatedAt(new \DateTimeImmutable());

        return $unresolvedXML;
    }

}