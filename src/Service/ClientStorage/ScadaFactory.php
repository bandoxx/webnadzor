<?php

namespace App\Service\ClientStorage;

use App\Entity\ClientStorage;
use App\Entity\ClientStorageDevice;
use App\Entity\ClientStorageDigitalEntry;
use App\Entity\ClientStorageText;
use App\Entity\Device;
use App\Factory\DeviceOverviewFactory;
use App\Model\Device\HumidityModel;
use App\Model\Device\TemperatureModel;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ScadaFactory
{

    public function __construct(
        private readonly DeviceOverviewFactory $deviceOverviewFactory,
        private readonly UrlGeneratorInterface $router
    ) {}

    /**
     * @param ClientStorage $clientStorage
     * @return ScadaModel[]
     */
    public function createFromClientStorage(ClientStorage $clientStorage): array
    {
        $models = [];
        foreach ($clientStorage->getTextInput() as $textInput) {
            $models[] = $this->createFromText($textInput);
        }

        foreach ($clientStorage->getDeviceInput() as $deviceInput) {
            $models[] = $this->createFromDeviceInput($deviceInput);
        }

        foreach ($clientStorage->getDigitalEntryInput() as $digitalEntry) {
            $models[] = $this->createFromDigitalEntry($digitalEntry);
        }

        return $models;
    }

    private function createFromText(ClientStorageText $textInput): ScadaModel
    {
        return (new ScadaModel())
            ->setType(ScadaModel::TEXT)
            ->setPositionX($textInput->getPositionX())
            ->setPositionY($textInput->getPositionY())
            ->setText($textInput->getText())
            ->setActiveBackground($textInput->isBackgroundActive())
            ->setFontColor($textInput->getFontColor())
            ->setFontSize($textInput->getFontSize())
            ->setUrl(null)
        ;
    }

    private function createFromDigitalEntry(ClientStorageDigitalEntry $digitalEntryInput): ScadaModel
    {
        $device = $digitalEntryInput->getDevice();
        $entry = $digitalEntryInput->getEntry();

        $deviceData = $this->deviceOverviewFactory->create($device, $entry);
        $deviceShowUrl = $this->getUrl($device, $entry);

        if (!$deviceData) {
            return (new ScadaModel())
                ->setType(ScadaModel::DEVICE)
                ->setPositionX($digitalEntryInput->getPositionX())
                ->setPositionY($digitalEntryInput->getPositionY())
                ->setText("Nema podataka.")
                ->setFontColor("#FF0000")
                ->setFontSize($digitalEntryInput->getFontSize())
                ->setActiveBackground(true)
                ->setUrl($deviceShowUrl)
            ;
        }

        if ($deviceData->getDigitalEntry()) {
            $text = $digitalEntryInput->getTextOn();
            $color = $digitalEntryInput->getFontColorOn();
        } else {
            $text = $digitalEntryInput->getTextOff();
            $color = $digitalEntryInput->getFontColorOff();
        }

        return (new ScadaModel())
            ->setType(ScadaModel::DEVICE)
            ->setPositionX($digitalEntryInput->getPositionX())
            ->setPositionY($digitalEntryInput->getPositionY())
            ->setText($text)
            ->setFontColor($color)
            ->setFontSize($digitalEntryInput->getFontSize())
            ->setActiveBackground($digitalEntryInput->isBackgroundActive())
            ->setUrl($deviceShowUrl)
        ;
    }

    private function createFromDeviceInput(ClientStorageDevice $deviceInput): ScadaModel
    {
        $device = $deviceInput->getDevice();
        $entry = $deviceInput->getEntry();
        $color = $deviceInput->getFontColor();
        $text = null;

        $deviceData = $this->deviceOverviewFactory->create($device, $entry);
        $deviceShowUrl = $this->getUrl($device, $entry);

        if (!$deviceData) {
            return (new ScadaModel())
                ->setType(ScadaModel::DEVICE)
                ->setPositionX($deviceInput->getPositionX())
                ->setPositionY($deviceInput->getPositionY())
                ->setText("Nema podataka.")
                ->setFontColor($deviceInput->getFontColor())
                ->setFontSize($deviceInput->getFontSize())
                ->setActiveBackground(true)
                ->setUrl($deviceShowUrl)
            ;
        }

        /** @var TemperatureModel $temperatureData */
        $temperatureData = $deviceData->getTemperatureModel();

        /** @var HumidityModel $humidityData */
        $humidityData = $deviceData->getHumidityModel();

        if ($deviceInput->getType() === ClientStorageDevice::TEMPERATURE_TYPE) {
            $temperatureData = $deviceData->getTemperatureModel();

            if ($temperatureData->getIsInOffset()) {
                $color = '#FF0000';
            }

            $text = sprintf("%s <br>%s",
                $temperatureData->getName(),
                $temperatureData->getCurrentWithUnit()
            );
        } elseif ($deviceInput->getType() === ClientStorageDevice::HUMIDITY_TYPE) {
            if ($humidityData->isInOffset()) {
                $color = '#FF0000';
            }

            $text = sprintf("%s <br>%s",
                $temperatureData->getName(),
                $humidityData->getCurrentWithUnit()
            );
        } elseif ($deviceInput->getType() === ClientStorageDevice::ALL_TYPE) {
            $humidityData = $deviceData->getHumidityModel();

            $text = sprintf("%s <br>", $temperatureData?->getName());

            if ($temperatureData?->isUsed()) {
                $text .= sprintf("Temp: %s <br> ", $temperatureData?->getCurrentWithUnit());
            }

            if ($humidityData?->isUsed()) {
                $text .= sprintf("Rh: %s", $humidityData?->getCurrentWithUnit());
            }

            //if ($deviceInput->isBackgroundActive()) {
            //    $color = '#FFFFFF';
            //}
        }

        return (new ScadaModel())
            ->setType(ScadaModel::DEVICE)
            ->setPositionX($deviceInput->getPositionX())
            ->setPositionY($deviceInput->getPositionY())
            ->setText($text)
            ->setFontColor($color)
            ->setFontSize($deviceInput->getFontSize())
            ->setActiveBackground($deviceInput->isBackgroundActive())
            ->setUrl($deviceShowUrl)
        ;
    }

    private function getUrl(Device $device, int $entry): string
    {
        return $this->router->generate('app_device_entry_show', [
            'clientId' => $device->getClient()->getId(),
            'id' => $device->getId(),
            'entry' => $entry
        ], UrlGeneratorInterface::ABSOLUTE_URL);
    }
}