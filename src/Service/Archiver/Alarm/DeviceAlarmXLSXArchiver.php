<?php

namespace App\Service\Archiver\Alarm;

use App\Entity\Device;
use App\Entity\DeviceAlarm;
use App\Service\Archiver\Archiver;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class DeviceAlarmXLSXArchiver extends Archiver implements DeviceAlarmArchiverInterface
{
    public function generate(Device $device, $data)
    {
        $xlsx = $this->generateBody($device, $data);
        $this->saveXLSX($xlsx);
    }

    private function generateBody(Device $device, array $data): Spreadsheet
    {
        $client = $device->getClient();
        $objPHPExcel = $this->prepareXLSX();

        $objPHPExcel->setActiveSheetIndex(0);
        $objPHPExcel->getActiveSheet()->setTitle('Arhiva podataka');
        $objPHPExcel->getActiveSheet()->setCellValue('A1', sprintf('Lista alarma za mjerni uređaj %s', $device->getName()));
        $objPHPExcel->getActiveSheet()->setCellValue('A2', sprintf('Datoteka generirana %s', (new \DateTime())->format('d.m.Y H:i:s')));
        $objPHPExcel->getActiveSheet()->setCellValue('A3', $client?->getName());
        $objPHPExcel->getActiveSheet()->getStyle('A1:H3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $objPHPExcel->getActiveSheet()->mergeCells('A1:C1');
        $objPHPExcel->getActiveSheet()->mergeCells('A2:C2');
        $objPHPExcel->getActiveSheet()->mergeCells('A3:C3');

        $objPHPExcel->getActiveSheet()->setCellValue('A5', 'Br.');
        $objPHPExcel->getActiveSheet()->setCellValue('B5', 'Datum');
        $objPHPExcel->getActiveSheet()->setCellValue('C5', 'Završni datum');
        $objPHPExcel->getActiveSheet()->setCellValue('D5', 'Aktivan');
        $objPHPExcel->getActiveSheet()->setCellValue('E5', 'Mjerno mjesto');
        $objPHPExcel->getActiveSheet()->setCellValue('F5', 'Vrsta alarma');

        $i = 5;
        $row = 0;

        /** @var DeviceAlarm $alarm */
        foreach ($data as $alarm) {
            $i++;

            $objPHPExcel->getActiveSheet()->setCellValue("A$i", ++$row);
            $objPHPExcel->getActiveSheet()->setCellValue("B$i", $alarm->getDeviceDate()->format('d.m.Y. H:i:s'));
            $objPHPExcel->getActiveSheet()->setCellValue("C$i", $alarm->getEndDeviceDate() ? $alarm->getEndDeviceDate()->format('d.m.Y. H:i:s') : '');
            $objPHPExcel->getActiveSheet()->setCellValue("D$i", $alarm->isActive() ? 'Da' : 'Ne');
            $objPHPExcel->getActiveSheet()->setCellValue("E$i", $alarm->getLocation());
            $objPHPExcel->getActiveSheet()->setCellValue("F$i", $alarm->getType());
        }

        if (empty($row)) {
            $objPHPExcel->getActiveSheet()->setCellValue('A6', 'Nema podataka');
            $objPHPExcel->getActiveSheet()->getStyle('A6:F6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $objPHPExcel->getActiveSheet()->mergeCells('A6:F6');
        }

        for ($aschar = 'A'; $aschar <= 'N'; $aschar++) {
            $objPHPExcel->getActiveSheet()->getColumnDimension($aschar)->setAutoSize(true);
        }

        $objPHPExcel->setActiveSheetIndex(0);

        return $objPHPExcel;
    }
}