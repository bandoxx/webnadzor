<?php

namespace App\Service\RawData;

use App\Service\Crypto\PNG\Encrypt;
use App\Service\Image\PdfToPngConverter;

class RawDataHandler
{

    public function __construct(
        private PdfToPngConverter $pdfToPngConverter,
        private Encrypt $encrypt,
        private string $projectTemporaryDirectory,
    ) {}

    public function encryptPdfFile(string $pdfFilePath, string $outputPath): void
    {
        $temporaryPNG = $this->projectTemporaryDirectory . '/image_' . uniqid() . '.png';
        $this->pdfToPngConverter->convert($pdfFilePath, $temporaryPNG);
        $this->encrypt->encrypt($temporaryPNG, $outputPath);

        if (file_exists($temporaryPNG)) {
            unlink($temporaryPNG);
        }
    }
}