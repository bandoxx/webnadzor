<?php

namespace App\Service\RawData\Factory;

use App\Entity\DeviceData;

class DeviceDataRawDataFactory
{

    /**
     * @param array<DeviceData> $deviceData
     * @param int $entry
     * @return array
     */
    public function create(array $deviceData, int $entry, \DateTime $fromDate, ?\DateTime $toDate = null): array
    {
        if (!$deviceData) {
            return [['Nema podataka']];
        }

        $device = $deviceData[0]->getDevice();

        if (!$device) {
            throw new \RuntimeException(sprintf('Device data not found for device data: %s', $deviceData->getId()));
        }

        $deviceEntryData = $device->getEntryData($entry);
        $tUnit = $deviceEntryData['t_unit'];
        $rhUnit = $deviceEntryData['rh_unit'];

        $dataset[] = [sprintf('Lokacija %s, mjerno mjesto %s', $device->getName(), $deviceEntryData['t_name'])];

        if ($fromDate && $toDate === null) {
            $dataset[] = [sprintf("Podaci za: %s", $fromDate->format("d.m.Y."))];
        } else {
            $dataset[] = [sprintf("Podaci od %s do %s", $fromDate->format('d.m.Y.'), $toDate->format("d.m.Y."))];
        }

        $dataset[] = [
            'Br.', 'Datum', 'Napomena', 'Tren', 'Max', 'Min', 'MKT', 'R. vlažnost'
        ];

        $i = 1;
        foreach ($deviceData as $data) {
            $dataset[] = [
                $i,
                $data->getDeviceDate()?->format("d.m.Y. H:i:s"),
                $data->getNote($entry) ?: '',
                sprintf("%s %s", $data->getT($entry), $tUnit),
                sprintf("%s %s", $data->getTMax($entry), $tUnit),
                sprintf("%s %s", $data->getTMin($entry), $tUnit),
                sprintf("%s %s", $data->getMkt($entry), $tUnit),
                sprintf("%s %s", $data->getRh($entry), $rhUnit)
            ];
            ++$i;
        }

        return $dataset;
    }

}