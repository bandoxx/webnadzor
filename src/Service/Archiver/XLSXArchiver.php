<?php

namespace App\Service\Archiver;

use App\Service\Archiver\Model\ArchiveModel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class XLSXArchiver extends Archiver
{

    public function prepare(): Spreadsheet
    {
        $objPHPExcel = new Spreadsheet();
        $objPHPExcel->getProperties()->setCreator('Intelteh d.o.o.')
            ->setLastModifiedBy('Intelteh d.o.o.')
            ->setTitle('Intelteh d.o.o.');

        return $objPHPExcel;
    }

    public function saveInMemory(Spreadsheet $spreadsheet): void
    {
        $objWriter = IOFactory::createWriter($spreadsheet, IOFactory::WRITER_XLSX);

        $objWriter->save('php://output');
    }

    public function save(Spreadsheet $spreadsheet, ?string $path = null, ?string $fileName = null): ArchiveModel
    {
        $objWriter = IOFactory::createWriter($spreadsheet, IOFactory::WRITER_XLSX);

        if (!is_dir($path) && !mkdir($path, 0755, true) && !is_dir($path)) {
            throw new \Exception("Cannot make archive directory $path");
        }

        $archiveModel = $this->createArchiveModel($path, $fileName);
        $objWriter->save($archiveModel->getFullPath());

        return $archiveModel;
    }
}