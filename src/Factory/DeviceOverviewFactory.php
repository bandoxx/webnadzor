<?php

namespace App\Factory;

use App\Entity\Device;
use App\Model\DeviceOverviewModel;
use App\Repository\DeviceAlarmRepository;
use App\Repository\DeviceDataRepository;
use App\Repository\DeviceIconRepository;

class DeviceOverviewFactory
{

    public function __construct(
        private readonly string               $iconDirectory,
        private readonly DeviceIconRepository $deviceIconRepository,
        private readonly DeviceDataRepository $deviceDataRepository,
        private readonly DeviceAlarmRepository $deviceAlarmRepository
    ) {}

    public function create(Device $device, int $entry): ?DeviceOverviewModel
    {
        $deviceOverviewModel = new DeviceOverviewModel();

        $data = $this->deviceDataRepository->findLastRecordForDeviceId($device->getId(), $entry);
        $numberOfAlarms = $this->deviceAlarmRepository->findNumberOfActiveAlarmsForDevice($device, $entry);

        if (!$data) {
            return null;
        }

        $online = false;
        if (time() - @strtotime($data->getDeviceDate()->format('Y-m-d H:i:s')) < 4200) {
            $online = true;
        }

        $deviceEntryData = $device->getEntryData($entry);

        $temperatureUnit = $deviceEntryData['t_unit'];
        $humidityUnit = $deviceEntryData['rh_unit'];

        $icon = sprintf("%s/%s", $this->iconDirectory, 'frizider.png');

        if ($deviceEntryData['t_image']) {
            $deviceIcon = $this->deviceIconRepository->find($deviceEntryData['t_image']);

            if ($deviceIcon) {
                $icon = sprintf("%s/%s", $this->iconDirectory, $deviceIcon->getFilename());
            }
        }

        $deviceOverviewModel
            ->setId($device->getId())
            ->setEntry($entry)
            ->setName($device->getName())
            ->setTemperatureName($deviceEntryData['t_name'] ?? null)
            ->setRelativeHumidityName($deviceEntryData['rh_name'] ?? null)
            ->setTemperatureLocation($deviceEntryData['t_location'] ?? null)
            ->setRelativeHumidityLocation($deviceEntryData['rh_location'] ?? null)
            ->setLocation($deviceEntryData['t_location'] ?? null)
            ->setOnline($online)
            ->setAlarm($numberOfAlarms > 0)
            ->setTemperature(sprintf("%s %s", $data->getT($entry), $temperatureUnit))
            ->setMeanKineticTemperature(sprintf("%s %s", $data->getMkt($entry), $temperatureUnit))
            ->setTemperatureMax(sprintf("%s %s", $data->getTMax($entry), $temperatureUnit))
            ->setTemperatureMin(sprintf("%s %s", $data->getTMin($entry), $temperatureUnit))
            ->setTemperatureAverage(sprintf("%s %s", $data->getTAvrg($entry), $temperatureUnit))
            ->setRelativeHumidity(sprintf("%s %s", $data->getRh($entry), $humidityUnit))
            ->setDeviceDate($data->getDeviceDate())
            ->setShowTemperature($deviceEntryData['t_show_chart'])
            ->setShowHumidity($deviceEntryData['rh_show_chart'])
            ->setTemperatureUnit($temperatureUnit)
            ->setRelativeHumidityUnit($humidityUnit)
            ->setTemperatureImage($icon)
        ;

        return $deviceOverviewModel;
    }
}