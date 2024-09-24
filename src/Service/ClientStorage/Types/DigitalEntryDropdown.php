<?php

namespace App\Service\ClientStorage\Types;

use App\Entity\Client;

class DigitalEntryDropdown
{

    public static function get(Client $client): array
    {
        $devices = $client->getDevice()->toArray();
        $list = [];

        foreach ($devices as $device) {
            for ($entry = 1; $entry <= 2; $entry++) {
                $text = sprintf("%s, %s", $device->getName(), $device->getEntryData($entry)['t_location']);

                if ($device->isDUsed($entry)) {
                    $list[] = [
                        'value' => sprintf("%s-%s", $device->getId(), $entry),
                        'text' => sprintf("%s, %s - Digitalni ulaz", $text,  $device->getEntryData($entry)['d_name']),
                    ];
                }
            }
        }

        return $list;
    }

}