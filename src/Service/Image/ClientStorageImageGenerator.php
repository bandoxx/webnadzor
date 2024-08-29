<?php

namespace App\Service\Image;

use App\Entity\ClientStorage;
use App\Factory\DeviceOverviewFactory;
use Symfony\Component\HttpKernel\KernelInterface;

class ClientStorageImageGenerator
{

    private string $publicDir;

    public function __construct(
        private readonly KernelInterface $kernel,
        private readonly DeviceOverviewFactory $deviceOverviewFactory,
        private readonly string $clientStorageDirectory
    ) {
        $this->publicDir = $this->kernel->getProjectDir() . '/public';

    }

    public function generateDeviceStorage(ClientStorage $clientStorage): void
    {
        $image = @ImageCreateFromPNG(sprintf("%s/%s", $this->clientStorageDirectory, $clientStorage->getImage()));
        imagealphablending($image, false);
        imagesavealpha($image, true);

        foreach ($clientStorage->getTextInput()->toArray() as $textInput) {
            $this->generateText($image, $textInput->getPositionX(), $textInput->getPositionY(), $textInput->getFontColor(), $textInput->getText());
        }

        foreach ($clientStorage->getDeviceInput()->toArray() as $deviceInput) {
            $color = $deviceInput->getFontColor();
            $deviceData = $this->deviceOverviewFactory->create($deviceInput->getDevice(), $deviceInput->getEntry());

            if (!$deviceData) {
                $this->generateText($image, $deviceInput->getPositionX(), $deviceInput->getPositionY(), '#FF0000', 'Nema podataka.');
                continue;
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

            $this->generateText($image, $deviceInput->getPositionX(), $deviceInput->getPositionY(), $color, $text);
        }

        ImagePNG($image);
        ImageDestroy($image);
    }

    private function generateText($image, $positionX, $positionY, $color, $text): void
    {
        $font = $this->publicDir.'/uploads/fonts/Satoshi-Bold.ttf';
        $color = str_split(ltrim($color, '#'), 2);

        [$red, $green, $blue] = [hexdec($color[0]), hexdec($color[1]), hexdec($color[2])];

        $color = ImageColorAllocate($image, $red, $green, $blue);

        imagettftext($image, 12,0, $positionX + 21, $positionY + 28, $color, $font, $text);
    }
}