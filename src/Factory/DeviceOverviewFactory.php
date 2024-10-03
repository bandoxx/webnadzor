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
        $deviceEntryData = $device->getEntryData($entry);

        $icon = sprintf("%s/%s", $this->iconDirectory, 'frizider.png');

        if ($deviceEntryData['t_image']) {
            $deviceIcon = $this->deviceIconRepository->find($deviceEntryData['t_image']);

            if ($deviceIcon) {
                $icon = sprintf("%s/%s", $this->iconDirectory, $deviceIcon->getFilename());
            }
        }

        $temperatureModel = (new TemperatureModel())
            ->setUnit($deviceEntryData['t_unit'])
            ->setLocation($deviceEntryData['t_location'] ?? null)
            ->setName($deviceEntryData['t_name'] ?? null)
            ->setIsShown((bool) $deviceEntryData['t_show_chart'])
            ->setIsUsed((bool) $deviceEntryData['t_use'])
            ->setImage($icon)
        ;

        $humidityModel = (new HumidityModel())
            ->setUnit($deviceEntryData['rh_unit'])
            ->setIsShown($deviceEntryData['rh_show_chart'])
            ->setIsUsed((bool) $deviceEntryData['rh_use'])
            ->setName($deviceEntryData['rh_name'] ?? null)
            ->setLocation($deviceEntryData['rh_location'] ?? null)
        ;

        $deviceOverviewModel = (new DeviceOverviewModel())
            ->setId($device->getId())
            ->setEntry($entry)
            ->setNote($deviceEntryData['t_note'] ?? null)
            ->setName($device->getName())
            ->setTemperatureModel($temperatureModel)
            ->setHumidityModel($humidityModel)
        ;

        $data = $this->deviceDataRepository->findLastRecordForDeviceId($device->getId(), $entry);
        $alarms = $this->deviceAlarmRepository->findActiveAlarms($device, $entry);

        if (!$data) {
            return $deviceOverviewModel;
        }

        $online = false;
        if (time() - $data->getDeviceDate()?->format('U') < $device->getXmlIntervalInSeconds()) {
            $online = true;
        }

        $temperatureModel
            ->setAverage($data->getTAvrg($entry))
            ->setMinimum($data->getTMin($entry))
            ->setMaximum($data->getTMax($entry))
            ->setMean($data->getMkt($entry))
            ->setCurrent($data->getT($entry))
            ->setIsInOffset($data->isTemperatureOutOfRange($entry))
        ;

        $humidityModel
            ->setCurrent($data->getRh($entry))
            ->setIsInOffset($data->isHumidityOutOfRange($entry))
        ;

        $deviceOverviewModel
            ->setSignal($data->getGsmSignal())
            ->setPower($data->getVbat())
            ->setBattery($data->getBattery())
            ->setOnline($online)
            ->setAlarms($alarms)
            ->setDeviceDate($data->getDeviceDate())
            ->setDigitalEntry($data->isD($entry))
        ;

        return $deviceOverviewModel;
    }
}