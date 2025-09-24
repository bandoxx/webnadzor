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

        if ($user->getPermission() > 1) {
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
        if ($user->getPermission() > 1) {
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
            // Limit accesses to this client only
            $accesses = $this->deviceAccessRepository->findBy(['user' => $user, 'client' => $client]);

            // Check if client-wide access exists
            $clientWide = false;
            foreach ($accesses as $acc) {
                if ($acc->getClient() && $acc->getDevice() === null) {
                    $clientWide = true;
                    break;
                }
            }

            if ($clientWide) {
                // Grant access to all used entries for all client's devices
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
                // Only include explicitly allowed device/sensor entries for this client
                foreach ($accesses as $access) {
                    $device = $access->getDevice();
                    if (!$device) {
                        continue;
                    }
                    // Skip entries for devices not belonging to this client (safety)
                    if ($device->getClient()?->getId() !== $client->getId()) {
                        continue;
                    }
                    $sensor = (int) $access->getSensor();
                    if ($sensor < 1 || $sensor > 2) {
                        continue;
                    }
                    $deviceData = $device->getEntryData($sensor);
                    if ($deviceData['t_use']) {
                        $entries[] = [
                            'entry' => $sensor,
                            'device' => $device
                        ];
                    }
                }
            }
        }

        return $entries;
    }

}