<?php

namespace App\Service\Device;

use App\Entity\Client;
use App\Entity\Device;
use App\Entity\User;
use App\Repository\DeviceRepository;
use App\Repository\UserDeviceAccessRepository;

class UserAccess
{

    public function __construct(
        private readonly DeviceRepository  $deviceRepository,
        private readonly UserDeviceAccessRepository $deviceAccessRepository
    )
    {}

    /**
     * @param User $user
     * @return array<Device>
     */
    public function getAccessibleDevices(Client $client, User $user): array
    {
        // TODO: Ako user nema attachovanog klijenta

        if ($user->getPermission() > 2) {
            $devices = $this->deviceRepository->findDevicesByClient($client->getId());
        } else {
            $accesses = $this->deviceAccessRepository->findAccessibleEntries($user);
            $devices = [];

            foreach ($accesses as $access) {
                $device = $access->getDevice();
                if (array_key_exists($device->getId(), $devices)) {
                    continue;
                }

                $devices[$device->getId()] = $device;
            }
        }

        return array_values($devices);
    }

    public function getAccessibleEntries(User $user, Client $client): array
    {
        $entries = [];
        if ($user->getPermission() > 2) {
            $devices = $this->deviceRepository->findDevicesByClient($client->getId());
            foreach ($devices as $device) {
                for ($entry = 1; $entry <= 2; $entry++) {
                    $data = $device->getEntryData($entry);
                    if ($data['t_use']) {
                        $entries[] = [
                            'entry' => $entry,
                            'device' => $device
                        ];
                    }
                }
            }
        } else {
            $accesses = $this->deviceAccessRepository->findAccessibleEntries($user);
            foreach ($accesses as $access) {
                $device = $access->getDevice();
                $deviceData = $device->getEntryData($access->getSensor());

                if ($deviceData['t_use']) {
                    $entries[] = [
                        'entry' => $access->getSensor(),
                        'device' => $access->getDevice()
                    ];
                }
            }

        }

        return $entries;
    }

}