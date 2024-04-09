<?php

namespace App\Service\Device;

use App\Entity\Device;
use App\Entity\User;
use App\Repository\DeviceRepository;
use App\Repository\UserDeviceAccessRepository;

class UserAccess
{

    public function __construct(
        private DeviceRepository $deviceRepository,
        private UserDeviceAccessRepository $deviceAccessRepository
    )
    {

    }

    /**
     * @param User $user
     * @return array<Device>
     */
    public function getAccessibleDevices(User $user): array
    {
        // TODO: Ako user nema attachovanog klijenta

        if ($user->getPermission() > 2) {
            $devices = $this->deviceRepository->findDevicesByClient($user->getClient());
        } else {
            $accesses = $this->deviceAccessRepository->findAccessibleEntries($user);
            $devices = [];

            foreach ($accesses as $access) {
                $device = $access['device'];
                if (array_key_exists($device->getId(), $devices)) {
                    continue;
                }

                $devices[$device->getId()] = $device;
            }
        }

        return array_values($devices);
    }

    public function getAccessibleEntries(User $user): array
    {
        $entries = [];
        if ($user->getPermission() > 2) {
            $devices = $this->deviceRepository->findDevicesByClient($user->getClient());
            foreach ($devices as $device) {
                for ($entry = 1; $entry <= 2; $entry++) {
                    $entries[] = [
                        'entry' => $entry,
                        'device' => $device
                    ];
                }
            }
        } else {
            $entries = $this->deviceAccessRepository->findAccessibleEntries($user);
        }

        return $entries;
    }

}