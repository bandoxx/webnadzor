<?php

namespace App\Service;

use App\Entity\Client;
use App\Entity\Device;
use App\Entity\User;
use App\Entity\UserDeviceAccess;
use App\Factory\DeviceOverviewFactory;
use App\Model\DeviceOverviewModel;
use App\Repository\DeviceRepository;
use App\Service\Device\UserAccess;

class DeviceLocationHandler
{

    public function __construct(
        private readonly DeviceRepository      $deviceRepository,
        private readonly UserAccess            $userAccess,
        private readonly DeviceOverviewFactory $deviceOverviewFactory,
    ) {}

    public function getUserDeviceLocations(User $user): array
    {
        /** @var UserDeviceAccess[] $accesses */
        $accesses = $user->getUserDeviceAccesses()->toArray();
        $deviceLocations = [];

        foreach ($accesses as $access) {
            $location = $this->getLocationByAccess($access);

            if ($location) {
                $deviceLocations[sprintf("%s-%s", $access->getDevice()->getId(), $access->getSensor())] = $location;
            }
        }

        return $deviceLocations;
    }

    public function getClientDeviceLocations(int $clientId): array
    {
        $devices = $this->deviceRepository->findDevicesByClient($clientId);

        $deviceLocations = [];

        foreach ($devices as $device) {
            $locations = $this->getLocationsForDevice($device);

            foreach ($locations as $key => $location) {
                $deviceLocations[$key] = $location;
            }
        }

        return $deviceLocations;
    }

    /**
     * @param User $user
     * @param Client $client
     * @return array<DeviceOverviewModel>
     */
    public function getClientDeviceLocationData(User $user, Client $client): array
    {
        $entries = $this->userAccess->getAccessibleEntries($user, $client);
        $deviceTable = [];

        foreach ($entries as $entry) {
            $overviewData = $this->deviceOverviewFactory->create($entry['device'], $entry['entry']);

            if (!$overviewData) {
                continue;
            }

            $deviceTable[] = $overviewData;
        }

        return $deviceTable;
    }

    private function getLocationByAccess(UserDeviceAccess $userDeviceAccess): array
    {
        $device = $userDeviceAccess->getDevice();
        $deviceEntryData = $device->getEntryData($userDeviceAccess->getSensor());

        if (!$deviceEntryData['t_use']) {
            return [];
        }

        return [
            'name' => $device->getName(),
            'location' => $deviceEntryData['t_name']
        ];
    }

    private function getLocationsForDevice(Device $device): array
    {
        $deviceLocations = [];

        for ($i = 1; $i <= 2; $i++) {
            $deviceEntryData = $device->getEntryData($i);

            if (!$deviceEntryData['t_use']) {
                continue;
            }

            $deviceLocations[sprintf("%s-%s", $device->getId(), $i)] = [
                'name' => $device->getName(),
                'location' => $deviceEntryData['t_name']
            ];
        }

        return $deviceLocations;
    }
}