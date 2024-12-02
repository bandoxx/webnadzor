<?php

namespace App\Service\SIM;

use App\Entity\Client;
use App\Entity\Device;
use App\Service\Archiver\SIM\SIMArchiverInterface;
use App\Service\Archiver\XLSXArchiver;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Psr\Log\LoggerInterface;

class SIMXSLXArchiver extends XLSXArchiver implements SIMArchiverInterface
{
    public function generate(Client $client, array $data): void
    {
        $xlsx = $this->generateBody($client, $data);
        $this->saveInMemory($xlsx);
    }

    public function generateAdmin(array $data): void
    {
        $xlsx = $this->generateBodyAdmin($data);
        $this->saveInMemory($xlsx);
    }

    private function generateBody(Client $client, array $data): Spreadsheet
    {
        try {
            $objPHPExcel = $this->prepare();

            $objPHPExcel->setActiveSheetIndex(0);
            $objPHPExcel->getActiveSheet()->setTitle('Arhiva podataka');
            $objPHPExcel->getActiveSheet()->setCellValue('A1', 'Lista SIM');
            $objPHPExcel->getActiveSheet()->setCellValue('A2', sprintf('Datoteka generirana %s', (new \DateTime())->format('d.m.Y H:i:s')));
            $objPHPExcel->getActiveSheet()->setCellValue('A3', $client?->getName());
            $objPHPExcel->getActiveSheet()->getStyle('A1:H3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $objPHPExcel->getActiveSheet()->mergeCells('A1:C1');
            $objPHPExcel->getActiveSheet()->mergeCells('A2:C2');
            $objPHPExcel->getActiveSheet()->mergeCells('A3:C3');

            $objPHPExcel->getActiveSheet()->setCellValue('A5', 'Br.');
            $objPHPExcel->getActiveSheet()->setCellValue('B5', 'XML Naziv');
            $objPHPExcel->getActiveSheet()->setCellValue('C5', 'Adresa');
            $objPHPExcel->getActiveSheet()->setCellValue('D5', 'Broj kartice');
            $objPHPExcel->getActiveSheet()->setCellValue('E5', 'Operater');

            $i = 5;
            $row = 0;

            /** @var Device $sim */
            foreach ($data as $sim) {
                $i++;

                $objPHPExcel->getActiveSheet()->setCellValue("A$i", ++$row);
                $objPHPExcel->getActiveSheet()->setCellValue("B$i", $sim->getXmlName());
                $objPHPExcel->getActiveSheet()->setCellValue("C$i", $sim->getClient()->getAddress());
                $objPHPExcel->getActiveSheet()->setCellValue("D$i", $sim->getSimPhoneNumber());
                $objPHPExcel->getActiveSheet()->setCellValue("E$i", $sim->getSimCardProvider());
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

        } catch (\Exception $e) {

        }
    }

    private function generateBodyAdmin(array $data): Spreadsheet
    {
        try {
            $objPHPExcel = $this->prepare();

            $objPHPExcel->setActiveSheetIndex(0);
            $objPHPExcel->getActiveSheet()->setTitle('Arhiva podataka');
            $objPHPExcel->getActiveSheet()->setCellValue('A1', 'Lista SIM');
            $objPHPExcel->getActiveSheet()->setCellValue('A2', sprintf('Datoteka generirana %s', (new \DateTime())->format('d.m.Y H:i:s')));
            $objPHPExcel->getActiveSheet()->setCellValue('A3', "Administracija");
            $objPHPExcel->getActiveSheet()->getStyle('A1:H3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $objPHPExcel->getActiveSheet()->mergeCells('A1:C1');
            $objPHPExcel->getActiveSheet()->mergeCells('A2:C2');
            $objPHPExcel->getActiveSheet()->mergeCells('A3:C3');

            $objPHPExcel->getActiveSheet()->setCellValue('A5', 'Br.');
            $objPHPExcel->getActiveSheet()->setCellValue('B5', 'XML Naziv');
            $objPHPExcel->getActiveSheet()->setCellValue('C5', 'Adresa');
            $objPHPExcel->getActiveSheet()->setCellValue('D5', 'Broj kartice');
            $objPHPExcel->getActiveSheet()->setCellValue('E5', 'Operater');

            $i = 5;
            $row = 0;

            /** @var Device $sim */
            foreach ($data as $sim) {
                $i++;

                $objPHPExcel->getActiveSheet()->setCellValue("A$i", ++$row);
                $objPHPExcel->getActiveSheet()->setCellValue("B$i", $sim->getXmlName());
                $objPHPExcel->getActiveSheet()->setCellValue("C$i", $sim->getClient()->getAddress());
                $objPHPExcel->getActiveSheet()->setCellValue("D$i", $sim->getSimPhoneNumber());
                $objPHPExcel->getActiveSheet()->setCellValue("E$i", $sim->getSimCardProvider());
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

        } catch (\Exception $e) {

        }
    }
}