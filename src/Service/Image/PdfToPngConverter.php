<?php

namespace App\Service\Image;

use Imagick;
use ImagickPixel;

class PdfToPngConverter
{
    public function convert(string $pdfFilePath, string $outputFilePath): void
    {
        $image = $this->getImagick($pdfFilePath);
        $numberOfImages = $image->getNumberImages();
        $totalHeight = 0;

        $pages = [];
        $this->destroyImagick($image);


        for ($i = 0; $i < $numberOfImages; $i++) {
            $page = new Imagick();
            $page->setResolution(300, 300);
            $page->readImage($pdfFilePath . "[$i]");

            // Get the original dimensions of the second page
            $newWidth = $page->getImageWidth();
            $newHeight = $page->getImageHeight();

            // Resize the second page
            $page->resizeImage($newWidth, $newHeight, Imagick::FILTER_LANCZOS, 1);

            // Set the output format to PNG
            $page->setImageFormat('png');

            $pages[] = $page;

            $totalHeight += $newHeight;
        }

        $newImage = new Imagick();
        $newImage->newImage($newWidth, $totalHeight, new ImagickPixel('white'));
        $newImage->setImageFormat('png');

        // Composite each page onto the blank canvas
        $yOffset = 0;
        foreach ($pages as $page) {
            $newImage->compositeImage($page, Imagick::COMPOSITE_DEFAULT, 0, $yOffset);
            $yOffset += $page->getImageHeight();  // Move the yOffset down by the height of the current image

            $this->destroyImagick($page);
        }

        // Write the second page as a PNG image to the specified output path
        $newImage->writeImage($outputFilePath);
    }

    private function getImagick(string $pdfFilePath): Imagick
    {
        $imagick = new Imagick();
        $imagick->readImage($pdfFilePath);

        return $imagick;
    }

    private function destroyImagick(Imagick $imagick): void
    {
        $imagick->clear();
        $imagick->destroy();
    }
}