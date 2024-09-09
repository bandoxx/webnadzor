<?php

namespace App\Service\Image;

use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class ImageResizer
{
    public static function resize(string $filePath, int $width, int $height): void
    {
        $manager = new ImageManager(
            new Driver()
        );

        $image = $manager->read($filePath);
        $image->scale($width, $height);
        $image->save($filePath);
    }
}