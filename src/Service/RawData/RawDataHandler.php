<?php

namespace App\Service\RawData;

use App\Service\Crypto\PNG\Decrypt;
use App\Service\Crypto\PNG\Encrypt;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class RawDataHandler
{

    public function __construct(
        private CSVHandler $csvHandler,
        private Encrypt    $encrypt,
        private Decrypt    $decrypt,
        private string     $projectTemporaryDirectory,
    ) {}

    public function encrypt(array $data, string $outputPath): void
    {
        $csvPath = $outputPath . '.csv';
        $this->csvHandler->write($data, $csvPath);
        $this->encrypt->encrypt($csvPath, $outputPath . '.enc');

        if (file_exists($csvPath)) {
            unlink($csvPath);
        }
    }

    public function decryptUploadedFile(UploadedFile $file): array
    {
        $outputFile = sys_get_temp_dir() . '/csv_'. uniqid() . '.csv';
        $this->decrypt->decrypt($file->getPathname(), $outputFile);

        $data = $this->csvHandler->read($outputFile);
        unlink($outputFile);

        return $data;
    }
}