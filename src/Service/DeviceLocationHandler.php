<?php

namespace App\Service;

use App\Entity\Client;
use App\Entity\Device;
use App\Entity\User;
use App\Entity\UserDeviceAccess;
use App\Model\DeviceOverviewModel;
use App\Repository\DeviceDataRepository;
use App\Repository\DeviceRepository;

class DeviceLocationHandler
{

    public function __construct(
        private DeviceRepository $deviceRepository,
        private DeviceDataRepository $deviceDataRepository
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

    public function getClientDeviceLocations(Client $client): array
    {
        $devices = $this->deviceRepository->findDevicesByClient($client);

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
    public function getClientDeviceLocationData(Client $client): array
    {
        $devices = $this->deviceRepository->findDevicesByClient($client);

        $deviceTable = [];
        foreach ($devices as $device) {
            for ($i = 1; $i <= 2; $i++) {
                $data = $this->deviceDataRepository->findLastRecordForDeviceId($device->getId(), $i);

                if (!$data) {
                    continue;
                }

                $online = false;
                if (time() - @strtotime($data->getDeviceDate()->format('Y-m-d H:i:s')) < 4200) {
                    $online = true;
                }

                $deviceOverviewModel = new DeviceOverviewModel();
                $deviceEntryData = $device->getEntryData($i);

                $temperatureUnit = $deviceEntryData['t_unit'];
                $humidityUnit = $deviceEntryData['rh_unit'];

                $deviceOverviewModel
                    ->setId($device->getId())
                    ->setEntry($i)
                    ->setName($deviceEntryData['t_name'] ?? null)
                    ->setLocation($deviceEntryData['t_location'] ?? null)
                    ->setOnline($online)
                    ->setAlarm(false)
                    ->setTemperature(sprintf("%s %s", $data->getT($i), $temperatureUnit))
                    ->setMeanKineticTemperature(sprintf("%s %s", $data->getMkt($i), $temperatureUnit))
                    ->setTemperatureMax(sprintf("%s %s", $data->getTMax($i), $temperatureUnit))
                    ->setTemperatureMin(sprintf("%s %s", $data->getTMin($i), $temperatureUnit))
                    ->setTemperatureAverage(sprintf("%s %s", $data->getTAvrg($i), $temperatureUnit))
                    ->setRelativeHumidity(sprintf("%s %s", $data->getRh($i), $humidityUnit))
                    ->setDeviceDate($data->getDeviceDate()->format("Y-m-d H:i:s"))
                ;

                $deviceTable[] = $deviceOverviewModel;
            }
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