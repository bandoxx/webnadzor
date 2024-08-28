<?php

namespace App\Factory;

use App\Entity\Device;
use App\Model\Device\DeviceOverviewModel;
use App\Model\Device\HumidityModel;
use App\Model\Device\TemperatureModel;
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
        $alarms = $this->deviceAlarmRepository->findActiveAlarms($device, $entry);

        if (!$data) {
            return null;
        }

        $online = false;
        if (time() - $data->getDeviceDate()?->format('U') < 5400) {
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

        $temperatureModel = (new TemperatureModel())
            ->setUnit($temperatureUnit)
            ->setAverage($data->getTAvrg($entry))
            ->setMinimum($data->getTMin($entry))
            ->setMaximum($data->getTMax($entry))
            ->setMean($data->getMkt($entry))
            ->setCurrent($data->getT($entry))
            ->setLocation($deviceEntryData['t_location'] ?? null)
            ->setName($deviceEntryData['t_name'] ?? null)
            ->setIsShown((bool) $deviceEntryData['t_show_chart'])
            ->setIsUsed((bool) $deviceEntryData['t_use'])
            ->setIsInOffset($data->isTemperatureOutOfRange($entry))
            ->setImage($icon)
        ;

        $humidityModel = (new HumidityModel())
            ->setUnit($humidityUnit)
            ->setCurrent($data->getRh($entry))
            ->setIsShown($deviceEntryData['rh_show_chart'])
            ->setIsInOffset($data->isHumidityOutOfRange($entry))
            ->setIsUsed((bool) $deviceEntryData['rh_use'])
            ->setName($deviceEntryData['rh_name'] ?? null)
            ->setLocation($deviceEntryData['rh_location'] ?? null)
        ;

        $deviceOverviewModel
            ->setId($device->getId())
            ->setEntry($entry)
            ->setName($device->getName())
            ->setSignal($data->getGsmSignal())
            ->setPower($data?->getVbat())
            ->setBattery($data?->getBattery())
            ->setOnline($online)
            ->setNote($deviceEntryData['t_note'] ?? null)
            ->setAlarms($alarms)
            ->setDeviceDate($data->getDeviceDate())
            ->setTemperatureModel($temperatureModel)
            ->setHumidityModel($humidityModel)
        ;

        return $deviceOverviewModel;
    }
}