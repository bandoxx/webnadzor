<?php

namespace App\Factory;

use App\Entity\Client;
use App\Entity\User;
use App\Service\Device\UserAccess;
use App\Service\PathToUrlConverter;

class MapMarkerFactory
{
    public function __construct(
        private string $mapMarkerDirectory,
        private UserAccess $userAccess,
        private PathToUrlConverter $pathToUrlConverter
    ) {}

    public function create(Client $client, User $user): array
    {
        $devices = $this->userAccess->getAccessibleDevices($client, $user);

        $icon = null;
        if ($client->getMapMarkerIcon()) {
            $mapMarkerIcon = sprintf("%s/%s", $this->mapMarkerDirectory, $client->getMapMarkerIcon());
            $icon = file_exists($mapMarkerIcon) ? $this->pathToUrlConverter->convertToAbsoluteUrl($mapMarkerIcon) : null;
        }

        $markers['places'] = [];
        $counter = [];

        foreach ($devices as $device) {
            $locationHash = sha1($device->getLatitude() . $device->getLongitude());

            if (array_key_exists($locationHash, $markers['places'])) {
                $counter[$locationHash]++;
            } else {
                $counter[$locationHash] = 1;
                $markers['places'][$locationHash] = [
                    'name' => $device->getName(),
                    'lat' => $device->getLatitude(),
                    'lng' => $device->getLongitude(),
                    'icon' => $icon
                ];
            }

            if ($counter[$locationHash] > 1) {
                $markers['places'][$locationHash]['name'] = sprintf("%s uređaja", $counter[$locationHash]);
            }
        }

        return array_values($markers['places']);
    }

}