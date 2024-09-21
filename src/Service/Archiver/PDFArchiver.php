<?php

namespace App\Service\Archiver;

use App\Service\Archiver\Model\ArchiveModel;

class PDFArchiver extends Archiver
{

    public function prepare(): \TCPDF
    {
        $pdf = new \TCPDF();
        $pdf->SetCreator('Intelteh d.o.o.');
        $pdf->SetAuthor('Intelteh d.o.o.');
        $pdf->SetTitle('Intelteh d.o.o.');

        return $pdf;
    }

    public function saveInMemory(\TCPDF $pdf): void
    {
        $pdf->Output('php://output');
    }

    public function save(\TCPDF $pdf, ?string $path = null, ?string $fileName = null): ArchiveModel
    {
        if (!is_dir($path) && !mkdir($path, 0755, true) && !is_dir($path)) {
            throw new \Exception("Cannot make archive directory $path");
        }

        $archiveModel = $this->createArchiveModel($path, $fileName);
        $pdf->Output($archiveModel->getFullPath(), 'F');

        return $archiveModel;
    }
}