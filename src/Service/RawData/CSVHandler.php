<?php

namespace App\Service\RawData;

class CSVHandler
{

    public function write(array $data, string $fileName): void
    {
        $file = fopen($fileName, 'wb');

        foreach ($data as $row) {
            fputcsv($file, $row);
        }

        fclose($file);
    }

    public function read(string $csvPath): array
    {
        $data = [];

        if (($file = fopen($csvPath, 'rb')) !== false) {
            while (($row = fgetcsv($file)) !== false) {
                $data[] = $row;
            }

            fclose($file);
        }

        return $data;
    }

}