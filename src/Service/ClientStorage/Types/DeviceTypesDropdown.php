<?php

namespace App\Service\ClientStorage\Types;

use App\Entity\Client;

class DeviceTypesDropdown
{

    public static function get(Client $client): array
    {
        $devices = $client->getDevice()->toArray();
        $list = [];

        foreach ($devices as $device) {
            for ($entry = 1; $entry <= 2; $entry++) {
                $text = sprintf("%s, %s", $device->getName(), $device->getEntryData($entry)['t_location']);

                //$list[] = [
                //    'value' => sprintf("%s-0-all", $device->getId()),
                //    'text' => sprintf("%s - Sve", $text)
                //];

                if ($device->isTUsed($entry)) {
                    $list[] = [
                        'value' => sprintf("%s-%s-t", $device->getId(), $entry),
                        'text' => sprintf("%s, %s - Temperatura", $text,  $device->getEntryData($entry)['t_name']),
                    ];
                }

                if ($device->isRhUsed($entry)) {
                    $list[] = [
                        'value' => sprintf("%s-%s-rh", $device->getId(), $entry),
                        'text' => sprintf("%s, %s - Vlaga", $text,  $device->getEntryData($entry)['rh_name']),
                    ];
                }
            }
        }

        return $list;
    }

}