<?php

namespace App\Service\Archiver;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use TCPDF;

class Archiver
{

    public function __construct(private readonly string $archiveDirectory) {}

    public function getArchiveDirectory(): string
    {
        return $this->archiveDirectory;
    }

    public function preparePDF(): TCPDF
    {
        $pdf = new TCPDF();
        $pdf->SetCreator('Intelteh d.o.o.');
        $pdf->SetAuthor('Intelteh d.o.o.');
        $pdf->SetTitle('Intelteh d.o.o.');

        return $pdf;
    }

    public function prepareXLSX(): Spreadsheet
    {
        $objPHPExcel = new Spreadsheet();
        $objPHPExcel->getProperties()->setCreator('Intelteh d.o.o.')
            ->setLastModifiedBy('Intelteh d.o.o.')
            ->setTitle('Intelteh d.o.o.');

        return $objPHPExcel;
    }

    public function saveXLSX(Spreadsheet $spreadsheet, $path = null, $fileName = null): void
    {
        $objWriter = IOFactory::createWriter($spreadsheet, IOFactory::WRITER_XLSX);

        if ($path && $fileName) {
            if (!is_dir($path) && !mkdir($path, 0755, true) && !is_dir($path)) {
                throw new \Exception("Cannot make archive directory $path");
            }

            $objWriter->save($path.$fileName);
        } else {
            $objWriter->save('php://output');
        }
    }

    public function savePDF(TCPDF $pdf, $path = null, $fileName = null): void
    {
        if ($path && $fileName) {
            if (!is_dir($path) && !mkdir($path, 0755, true) && !is_dir($path)) {
                throw new \Exception("Cannot make archive directory $path");
            }

            $pdf->Output($path.$fileName, 'F');
        } else {
            $pdf->Output('php://output');
        }
    }
}