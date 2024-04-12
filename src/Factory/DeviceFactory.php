<?php

namespace App\Factory;

use App\Entity\Client;
use App\Entity\Device;

class DeviceFactory
{

    public function create(Client $client, string $deviceName, string $xmlName): Device
    {
        $device = new Device();
        $device->setName($deviceName)
            ->setClient($client)
            ->setXmlName($xmlName)
            ->setParserActive(false)
        ;

        $emptyEntry = [
            't_location' => null,
            't_name' => null,
            't_unit' => null,
            't_image' => null,
            't_show_chart' => null,
            't_min' => null,
            't_max' => null,
            'rh_name' => null,
            'rh_unit' => null,
            'rh_image' => null,
            'rh_show_chart' => null,
            'rh_min' => null,
            'd_name' => null,
            'd_off_name' => null,
            'd_on_name' => null,
            'd_off_image' => null,
            'd_on_image' => null,
            't_use' => null,
            'rh_use' => null,
            'd_use' => null
        ];

        $device->setEntry1($emptyEntry);
        $device->setEntry2($emptyEntry);

        return $device;
    }

}