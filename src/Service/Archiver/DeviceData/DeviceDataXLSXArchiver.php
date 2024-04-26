<?php

namespace App\Service\Archiver\DeviceData;

use App\Entity\Device;
use App\Entity\DeviceData;
use App\Service\Archiver\Archiver;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class DeviceDataXLSXArchiver extends Archiver implements DeviceDataArchiverInterface
{
    public function saveCustom(Device $device, array $deviceData, $entry, \DateTime $fromDate, \DateTime $toDate, ?string $fileName = null)
    {
        $subtitle = sprintf("Podaci od %s do %s", $fromDate->format(self::DAILY_FORMAT), $toDate->format(self::DAILY_FORMAT));
        $xlsx = $this->generateBody($device, $deviceData, $entry, $subtitle);

        $this->saveXLSX($xlsx, $fileName);
    }

    public function saveDaily(Device $device, array $deviceData, $entry, \DateTime $archiveDate, ?string $fileName = null)
    {
        $subtitle = sprintf("Podaci za %s", $archiveDate->format(self::DAILY_FORMAT));
        $xlsx = $this->generateBody($device, $deviceData, $entry, $subtitle);
        $client = $device->getClient();

        $fileName = sprintf("%s.xlsx", $fileName);
        $path = sprintf('%s/%s/daily/%s/', $this->getArchiveDirectory(), $client->getId(), $archiveDate->format('Y/m/d'));
        $this->saveXLSX($xlsx, $path, $fileName);
    }

    public function saveMonthly(Device $device, array $deviceData, $entry, \DateTime $archiveDate, ?string $fileName = null)
    {
        $subtitle = sprintf("Podaci za %s", $archiveDate->format(self::MONTHLY_FORMAT));
        $xlsx = $this->generateBody($device, $deviceData, $entry, $subtitle);
        $client = $device->getClient();

        $fileName = sprintf("%s.xlsx", $fileName);
        $path = sprintf('%s/%s/monthly/%s/', $this->getArchiveDirectory(), $client->getId(), $archiveDate->format('Y/m/d'));
        $this->saveXLSX($xlsx, $path, $fileName);
    }

    private function generateBody(Device $device, array $deviceData, $entry, $subtitle): Spreadsheet
    {
        $deviceEntryData = $device->getEntryData($entry);
        $tUnit = $deviceEntryData['t_unit'];
        $rhUnit = $deviceEntryData['rh_unit'];

        $objPHPExcel = $this->prepareXLSX();

        $objPHPExcel->setActiveSheetIndex(0);
        $objPHPExcel->getActiveSheet()->setTitle('Arhiva podataka');
        $objPHPExcel->getActiveSheet()->setCellValue('A1', sprintf('Lokacija %s, mjerno mjesto %s', $device->getName(), $deviceEntryData['t_name']));
        $objPHPExcel->getActiveSheet()->setCellValue('A2', $subtitle);
        $objPHPExcel->getActiveSheet()->getStyle('A1:H3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $objPHPExcel->getActiveSheet()->mergeCells('A1:H1');
        $objPHPExcel->getActiveSheet()->mergeCells('A2:H2');
        $objPHPExcel->getActiveSheet()->mergeCells('A3:H3');

        $objPHPExcel->getActiveSheet()->setCellValue('A5', 'Br.');
        $objPHPExcel->getActiveSheet()->setCellValue('B5', 'Datum');
        $objPHPExcel->getActiveSheet()->setCellValue('C5', 'Napomena');

        if (!empty($tUnit)) {
            $unit = '(' . $tUnit . ')';
        } else {
            $unit = '';
        }

        $objPHPExcel->getActiveSheet()->setCellValue('D4', sprintf("%s %s", $deviceEntryData['t_name'], $unit));
        $objPHPExcel->getActiveSheet()->setCellValue('D5', sprintf('Tren (%s)', $tUnit));
        $objPHPExcel->getActiveSheet()->setCellValue('E5', sprintf('Max (%s)', $tUnit));
        $objPHPExcel->getActiveSheet()->setCellValue('F5', sprintf('Min (%s)', $tUnit));
        $objPHPExcel->getActiveSheet()->setCellValue('G5', sprintf('MKT (%s)', $tUnit));
        $objPHPExcel->getActiveSheet()->setCellValue('H5', sprintf('R. vlažnost (%s)', $rhUnit));
        $objPHPExcel->getActiveSheet()->mergeCells('D4:G4');
        $objPHPExcel->getActiveSheet()->getStyle('D4:G4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $objPHPExcel->getActiveSheet()->getStyle('A1:D3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $objPHPExcel->getActiveSheet()->mergeCells('A1:D1');
        $objPHPExcel->getActiveSheet()->mergeCells('A2:D2');
        $objPHPExcel->getActiveSheet()->mergeCells('A3:D3');

        $i = 5;
        $row = 0;

        /** @var DeviceData $data */
        foreach ($deviceData as $data) {
            if (!$data->getT($entry) && !$data->getRh($entry)) {
                continue;
            }

            $i++;

            $objPHPExcel->getActiveSheet()->setCellValue("A$i", ++$row);
            $objPHPExcel->getActiveSheet()->setCellValue("B$i", $data->getDeviceDate()->format('d.m.Y. H:i:s'));
            $objPHPExcel->getActiveSheet()->setCellValue("C$i", $data->getNote($entry));

            if ($data->isTemperatureOutOfRange($entry)) {
                $objPHPExcel->getActiveSheet()->setCellValue("D$i", $data->getT($entry))->getStyle("D$i")->getFont()->getColor()->setRGB('FF0000');
            } else {
                $objPHPExcel->getActiveSheet()->setCellValue("D$i", $data->getT($entry));
            }
            $objPHPExcel->getActiveSheet()->setCellValue("G$i", $data->getTMax($entry));
            $objPHPExcel->getActiveSheet()->setCellValue("E$i", $data->getTMin($entry));
            $objPHPExcel->getActiveSheet()->setCellValue("F$i", $data->getTAvrg($entry));

            if ($data->isHumidityOutOfRange($entry)) {
                $objPHPExcel->getActiveSheet()->setCellValue("H$i", $data->getRh($entry))->getStyle("D$i")->getFont()->getColor()->setRGB('FF0000');
            } else {
                $objPHPExcel->getActiveSheet()->setCellValue("H$i", $data->getRh($entry));
            }
        }

        if (empty($row)) {
            $objPHPExcel->getActiveSheet()->setCellValue('A6', 'Nema podataka');
            $objPHPExcel->getActiveSheet()->getStyle('A6:H6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $objPHPExcel->getActiveSheet()->mergeCells('A6:H6');
        }

        for ($aschar = 'A'; $aschar <= 'N'; $aschar++) {
            $objPHPExcel->getActiveSheet()->getColumnDimension($aschar)->setAutoSize(true);
        }

        $objPHPExcel->setActiveSheetIndex(0);

        return $objPHPExcel;
    }
}