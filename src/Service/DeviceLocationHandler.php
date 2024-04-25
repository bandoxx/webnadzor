<?php

namespace App\Service;

use App\Entity\Client;
use App\Entity\Device;
use App\Entity\User;
use App\Entity\UserDeviceAccess;
use App\Model\DeviceOverviewModel;
use App\Repository\ClientRepository;
use App\Repository\DeviceAlarmRepository;
use App\Repository\DeviceDataRepository;
use App\Repository\DeviceRepository;
use App\Service\Device\UserAccess;

class DeviceLocationHandler
{

    public function __construct(
        private DeviceRepository $deviceRepository,
        private DeviceDataRepository $deviceDataRepository,
        private DeviceAlarmRepository $deviceAlarmRepository,
        private UserAccess $userAccess
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

    public function getClientDeviceLocations($clientId): array
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
     * @param Client $client
     * @return array<DeviceOverviewModel>
     */
    public function getClientDeviceLocationData(User $user, Client $client): array
    {
        $entries = $this->userAccess->getAccessibleEntries($user, $client);
        $deviceTable = [];

        foreach ($entries as $entry) {
            $device = $entry['device'];
            $entryN = $entry['entry'];

            $data = $this->deviceDataRepository->findLastRecordForDeviceId($device->getId(), $entryN);
            $numberOfAlarms = $this->deviceAlarmRepository->findNumberOfActiveAlarmsForDevice($device);

            if (!$data) {
                continue;
            }

            $online = false;
            if (time() - @strtotime($data->getDeviceDate()->format('Y-m-d H:i:s')) < 4200) {
                $online = true;
            }

            $deviceOverviewModel = new DeviceOverviewModel();
            $deviceEntryData = $device->getEntryData($entryN);

            $temperatureUnit = $deviceEntryData['t_unit'];
            $humidityUnit = $deviceEntryData['rh_unit'];

            $deviceOverviewModel
                ->setId($device->getId())
                ->setEntry($entryN)
                ->setName($deviceEntryData['t_name'] ?? null)
                ->setLocation($deviceEntryData['t_location'] ?? null)
                ->setOnline($online)
                ->setAlarm($numberOfAlarms > 0)
                ->setTemperature(sprintf("%s %s", $data->getT($entryN), $temperatureUnit))
                ->setMeanKineticTemperature(sprintf("%s %s", $data->getMkt($entryN), $temperatureUnit))
                ->setTemperatureMax(sprintf("%s %s", $data->getTMax($entryN), $temperatureUnit))
                ->setTemperatureMin(sprintf("%s %s", $data->getTMin($entryN), $temperatureUnit))
                ->setTemperatureAverage(sprintf("%s %s", $data->getTAvrg($entryN), $temperatureUnit))
                ->setRelativeHumidity(sprintf("%s %s", $data->getRh($entryN), $humidityUnit))
                ->setDeviceDate($data->getDeviceDate()->format("Y-m-d H:i:s"))
            ;

            $deviceTable[] = $deviceOverviewModel;
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