<?php

namespace App\Service\RawData;

use App\Service\Crypto\PNG\Decrypt;
use App\Service\Crypto\PNG\Encrypt;
use App\Service\Image\PdfToPngConverter;

class RawDataHandler
{

    public function __construct(
        private PdfToPngConverter $pdfToPngConverter,
        private Encrypt $encrypt,
        private Decrypt $decrypt,
    ) {}

    public function encryptPdfToPng(string $pdfFilePath, string $outputPath): void
    {
        $temporaryPNG = sys_get_temp_dir() . '.png';
        $this->pdfToPngConverter->convert($pdfFilePath, $temporaryPNG);
        $this->encrypt->encrypt($temporaryPNG, $outputPath);

        if (file_exists($temporaryPNG)) {
            unlink($temporaryPNG);
        }
    }

    public function decrypt()
    {
        // TODO:
    }

}