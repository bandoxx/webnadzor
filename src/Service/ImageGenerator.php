<?php

namespace App\Service;

use App\Repository\DeviceDataRepository;
use Symfony\Component\HttpKernel\KernelInterface;

class ImageGenerator
{

    private string $publicDir;

    public function __construct(private DeviceDataRepository $deviceDataRepository, private KernelInterface $kernel)
    {
        $this->publicDir = $this->kernel->getProjectDir() . '/public';
    }

    public function generateTermometer($deviceId, $entry): void
    {
        $data = $this->deviceDataRepository->findLastRecordForDeviceId($deviceId, $entry);

        $temperature = $data?->getT($entry);

        $im = @ImageCreateFromPNG($this->publicDir .'/assets/images/termostat.png');
        imagealphablending($im, false);
        imagesavealpha($im, true);
        $blue = ImageColorAllocate($im, 50, 110, 176);
        $red = ImageColorAllocate($im, 255, 0, 0);

        $rx = 74;
        $ry = 246;
        $rWidth = 4;

        if ($temperature) {
            $rHeight = 238-196/90*(40 + (float) $temperature);
            $fillColor = $blue;
        } else {
            $rHeight = 42;
            imagestring($im, 3, 52, 18, 'Greska!', $red);
            $fillColor = $red;
        }

        ImageFilledRectangle($im, $rx, $ry, $rx+$rWidth, $rHeight, $fillColor);

        $this->checkFolder($deviceId, $entry);

        ImagePNG($im, sprintf("%s/assets/images/%s/%s/termostat.png", $this->publicDir, $deviceId, $entry), 0, null);
    }

    private function checkFolder($deviceId, $entry)
    {

        if (!file_exists(sprintf("%s/assets/images/devices", $this->publicDir))) {
            if (!mkdir($concurrentDirectory = sprintf("%s/assets/images/devices", $this->publicDir)) && !is_dir($concurrentDirectory)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
            }
        }

        if (!file_exists(sprintf("%s/assets/images/devices/%s", $this->publicDir, $deviceId))) {
            if (!mkdir($concurrentDirectory = sprintf("%s/assets/images/devices/%s", $this->publicDir, $deviceId)) && !is_dir($concurrentDirectory)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
            }
        }

        if (!file_exists(sprintf("%s/assets/images/devices/%s/%s", $this->publicDir, $deviceId, $entry))) {
            if (!mkdir($concurrentDirectory = sprintf("%s/assets/images/devices/%s/%s", $this->publicDir, $deviceId, $entry)) && !is_dir($concurrentDirectory)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
            }
        }
    }
}