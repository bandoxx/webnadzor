<?php

namespace App\Service\ClientStorage;

use App\Entity\ClientStorage;
use App\Entity\ClientStorageDevice;
use App\Entity\ClientStorageText;
use App\Factory\DeviceOverviewFactory;
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

    private function createFromDeviceInput(ClientStorageDevice $deviceInput): ScadaModel
    {
        $device = $deviceInput->getDevice();
        $entry = $deviceInput->getEntry();
        $color = $deviceInput->getFontColor();
        $text = null;

        $deviceData = $this->deviceOverviewFactory->create($device, $entry);
        $deviceShowUrl = $this->router->generate('app_device_entry_show', [
            'clientId' => $device->getClient()->getId(),
            'id' => $device->getId(),
            'entry' => $entry
        ], UrlGeneratorInterface::ABSOLUTE_URL);

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

        if ($deviceInput->getType() === 't') {
            $temperatureData = $deviceData->getTemperatureModel();

            if ($temperatureData->getIsInOffset()) {
                $color = '#FF0000';
            }

            $text = $temperatureData->getCurrentWithUnit();
        } elseif ($deviceInput->getType() === 'rh') {
            $humidityData = $deviceData->getHumidityModel();

            if ($humidityData->isInOffset()) {
                $color = '#FF0000';
            }

            $text = $humidityData->getCurrentWithUnit();
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
}