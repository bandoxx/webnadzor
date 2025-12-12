<?php

namespace App\Service\ClientStorage\Types;

use App\Entity\Client;
use App\Repository\DeviceRepository;

class DeviceTypesDropdown
{
    public function __construct(
        private readonly DeviceRepository $deviceRepository
    ) {
    }

    public function getForClient(Client $client): array
    {
        $devices = $client->getDevice()->toArray();
        $list = [];

        foreach ($devices as $device) {
            for ($entry = 1; $entry <= 2; $entry++) {
                $text = sprintf("%s, %s", $device->getName(), $device->getEntryData($entry)['t_location']);
                $isTUsed = $device->isTUsed($entry);
                if ($isTUsed) {
                    $list[] = [
                        'value' => sprintf("%s", $device->getId()),
                        'text' => sprintf("%s, %s", $text, $device->getEntryData($entry)['t_name']),
                    ];
                }
            }
        }

        return $list;
    }

    public function getAllDevices(): array
    {
        $devices = $this->deviceRepository->findActiveDevices();
        $result = [];

        foreach ($devices as $device) {
            for ($entry = 1; $entry <= 2; $entry++) {
                if ($device->isTUsed($entry) || $device->isRhUsed($entry)) {
                    $entryData = $device->getEntryData($entry);

                    $entryName = $device->isTUsed($entry) && !empty($entryData['t_name'])
                        ? $entryData['t_name']
                        : ($entryData['rh_name'] ?? '');

                    $result[] = [
                        'value' => sprintf('%s', $device->getId()),
                        'text'  => $device->getName() . ' - ' . $entryName,
                    ];
                }
            }
        }

        return $result;
    }
}