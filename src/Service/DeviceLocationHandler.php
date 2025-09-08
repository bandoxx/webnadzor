<?php

namespace App\Service;

use App\Entity\Client;
use App\Entity\Device;
use App\Entity\User;
use App\Entity\UserDeviceAccess;
use App\Factory\DeviceOverviewFactory;
use App\Model\Device\DeviceOverviewModel;
use App\Repository\ClientRepository;
use App\Repository\DeviceRepository;
use App\Service\Device\UserAccess;

class DeviceLocationHandler
{

    public function __construct(
        private readonly DeviceRepository      $deviceRepository,
        private readonly UserAccess            $userAccess,
        private readonly DeviceOverviewFactory $deviceOverviewFactory,
        private readonly ClientRepository      $clientRepository,
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

    public function getClientDeviceLocations(int $clientId, bool $includeClientInKey = false): array
    {
        $devices = $this->deviceRepository->findDevicesByClient($clientId);

        $deviceLocations = [];

        foreach ($devices as $device) {
            $locations = $this->getLocationsForDevice($device, $includeClientInKey);

            foreach ($locations as $key => $location) {
                $deviceLocations[$key] = $location;
            }
        }

        return $deviceLocations;
    }

    public function getAllClientDeviceLocations(): array
    {
        $clients = $this->clientRepository->findBy([], ['name' => 'ASC']);
        $locations = [];

        foreach ($clients as $client) {
            $data = $this->getClientDeviceLocations($client->getId(), true);
            if (!$data) {
                continue;
            }

            $locations[] = $data;
        }

        return array_merge(...$locations);
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
            'client_id' => $device->getClient()->getId(),
            'device_id' => $device->getId(),
            'entry' => $userDeviceAccess->getSensor(),
            'name' => $device->getName(),
            'location' => $deviceEntryData['t_name']
        ];
    }

    private function getLocationsForDevice(Device $device, bool $includeClientInKey = false): array
    {
        $deviceLocations = [];

        for ($i = 1; $i <= 2; $i++) {
            $deviceEntryData = $device->getEntryData($i);

            if (!$deviceEntryData['t_use']) {
                continue;
            }

            $deviceName = $device->getName();
            $location = $deviceEntryData['t_name'];

            if ($includeClientInKey) {
                $key = sprintf("%s-%s-%s", $device->getClient()->getId(), $device->getId(), $i);
            } else {
                $key = sprintf("%s-%s", $device->getId(), $i);
            }

            $deviceLocations[$key] = [
                'name' => $deviceName,
                'location' => $location,
                'full' => sprintf("%s, %s", $deviceName, $location)
            ];
        }

        return $deviceLocations;
    }
}