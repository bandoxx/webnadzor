<?php

namespace App\Service\Image;

use App\Repository\DeviceDataRepository;
use Symfony\Component\HttpKernel\KernelInterface;

class ImageGenerator
{

    private string $publicDir;
    private array $data = [];

    public function __construct(
        private readonly DeviceDataRepository $deviceDataRepository,
        private readonly KernelInterface $kernel
    ) {
        $this->publicDir = $this->kernel->getProjectDir() . '/public';

    }

    public function generateDeviceStorage($clientStorageData,array $storage) {

        $this->getDeviceImage($clientStorageData,$storage);

    }

    public function getDeviceImage($clientStorageData,$storages): void
    {
        $im = @ImageCreateFromPNG($this->publicDir .$clientStorageData->getBaseImage());
        $languageFile = $this->publicDir.'/uploads/fonts/OpenSans-Regular.ttf';
        imagealphablending($im, false);
        imagesavealpha($im, true);

        foreach ($storages as $storage){

            $temperature = null;
            if (array_key_exists('d_device_id',$storage)){
                $data = $this->getDeviceData($storage['d_device_id'], $storage['d_entry']);

                $temperature = $data?->getT($storage['d_entry']);
            }

            $cleanedString = str_replace(['rgb(', ')'], '', $storage['d_font_color']);

            // Split the string by commas
            list($red, $green, $blue) = explode(',', $cleanedString);

            // Trim any whitespace from the values
            $red = trim($red);
            $green = trim($green);
            $blue = trim($blue);

            $color = ImageColorAllocate($im, $red, $green, $blue);

            $rx = $storage['d_position_x'];
            $ry = $storage['d_position_y'];

            if (!is_null($temperature)){
                imagettftext($im,$storage['d_font_size'],0,$rx,$ry,$color,$languageFile,$temperature);
            }else{
                imagettftext($im,$storage['d_font_size'],0,$rx,$ry,$color,$languageFile,$storage['d_placeholder_text']);
            }
        }

        ImagePNG($im);
        ImageDestroy($im);
    }

    public function generateThermometer(int $deviceId, int $entry): void
    {
        $data = $this->getDeviceData($deviceId, $entry);

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

        ImagePNG($im);
        ImageDestroy($im);
    }

    public function generateRelativyHumidity(int $deviceId, int $entry): void
    {
        $data = $this->getDeviceData($deviceId, $entry);

        $rh = $data?->getRh($entry);

        $im = @ImageCreateFromPNG($this->publicDir . '/assets/images/rh_scale.png');
        imagealphablending($im, true);
        imagesavealpha($im, true);

        $im_dial = @ImageCreateFromPNG($this->publicDir . '/assets/images/rh_dial.png');
        imagealphablending($im_dial, false);
        imagesavealpha($im_dial, true);

        // create a new image from the sizes on transparent canvas
        $new = imagecreatetruecolor(imagesx($im_dial), imagesy($im_dial));

        if ($rh > 100) {
            $rh = 100;
        } elseif ($rh < 0) {
            $rh = 0;
        }

        $dataset = [
            [0.0, 136],
            [10, 99],
            [20, 67.5],
            [30, 38],
            [40, 10],
            [50, -17],
            [60, -45],
            [70, -70.5],
            [80, -96],
            [90, -117],
            [100.0, -132]
        ];

        $n = count($dataset);

        // Calculate Lagrange basis polynomials
        $polynomials = [];
        for ($k = 0; $k < $n; $k++) {
            $polynomials[$k] = 1;
            foreach ($dataset as $i => $iValue) {
                if ($i !== $k) {
                    $polynomials[$k] *= ($rh - $iValue[0]) / ($dataset[$k][0] - $iValue[0]);
                }
            }
        }

        // Calculate interpolated value
        $rdeg = 0;
        for ($k = 0; $k < $n; $k++) {
            $rdeg += $dataset[$k][1] * $polynomials[$k];
        }

        $transparent = imagecolorallocatealpha($new, 0, 0, 0, 127);
        $rotate = imagerotate($im_dial, $rdeg, $transparent);
        imagealphablending($rotate, true);
        imagesavealpha($rotate, true);

        // get the newest image X and Y
        $ix = imagesx($rotate);
        $iy = imagesy($rotate);

        //copy the image to the canvas
        imagecopy($im, $rotate, 80-$ix/2, 80-$iy/2, 0, 0, $ix, $iy);

        ImagePNG($im);
        ImageDestroy($im);
    }

    private function getDeviceData(int $deviceId, int $entry)
    {
        $key = sprintf("%s-%s", $deviceId, $entry);

        if (array_key_exists($key, $this->data)) {
            return $this->data[$key];
        }

        $this->data[$key] = $this->deviceDataRepository->findLastRecordForDeviceId($deviceId, $entry);

        return $this->data[$key];
    }
}