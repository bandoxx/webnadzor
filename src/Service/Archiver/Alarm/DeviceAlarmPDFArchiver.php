<?php

namespace App\Service\Archiver\Alarm;

use App\Entity\Device;
use App\Entity\DeviceAlarm;
use App\Service\Archiver\Archiver;

class DeviceAlarmPDFArchiver extends Archiver implements DeviceAlarmArchiverInterface
{
    public function generate(Device $device, $data): void
    {
        $client = $device->getClient();
        $pdf = $this->preparePDF();

        // set default header data
        $headerData = sprintf('Datoteka generirana %s', (new \DateTime())->format('d.m.Y H:i:s')) . "\n";
        $headerData .= $client?->getName();

        $logo = $client?->getPdfLogo();
        $pdf->SetHeaderData($logo ? "../../../../../public/assets/images/logo/$logo" : '', 30, sprintf('Lista alarma za mjerni uređaj %s', $device->getName()), $headerData);

        // set header and footer fonts
        $pdf->setHeaderFont(['dejavusanscondensed', '', 8]);
        $pdf->setFooterFont(['dejavusanscondensed', '', 8]);

        // set default monospaced font
        $pdf->SetDefaultMonospacedFont('courier');

        // set margins
        $pdf->SetMargins(15, 27, 15);
        $pdf->SetHeaderMargin(5);

        // set auto page breaks
        $pdf->SetAutoPageBreak(true, 25);

        // set image scale factor
        $pdf->setImageScale(1.25);

        // add a page
        $pdf->AddPage();
        $pdf->SetFont('helvetica', '', 8);

        // Colors, line width and bold font
        $pdf->SetFillColor(196, 202, 211);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetDrawColor(0, 0, 0);
        $pdf->SetLineWidth(0.25);

        $pdf->Cell($pdf->pixelsToUnits(30), 4, 'Br.', 1, 0, 'L', true);
        $pdf->Cell($pdf->pixelsToUnits(105), 4, 'Datum', 1, 0, 'L', true);
        $pdf->Cell($pdf->pixelsToUnits(105), 4, 'Završni datum', 1, 0, 'L', true);
        $pdf->Cell($pdf->pixelsToUnits(60), 4, 'Aktivan', 1, 0, 'L', true);
        $pdf->Cell($pdf->pixelsToUnits(100), 4, 'Mjerno mjesto', 1, 0, 'L', true);
        $pdf->Cell($pdf->pixelsToUnits(100), 4, 'Vrsta alarma', 1, 0, 'L', true);
        $pdf->Ln();

        $i = 1;
        $row = 0;
        $fill = true;

        $pdf->SetFillColor(238, 238, 238);

        /** @var DeviceAlarm $alarm */
        foreach ($data as $alarm) {
            $i++;
            $pdf->Cell($pdf->pixelsToUnits(30), 4, ++$row, 1, 0, 'L', $fill);
            $pdf->Cell($pdf->pixelsToUnits(105), 4, $alarm->getDeviceDate()->format('d.m.Y. H:i:s'), 1, 0, 'L', $fill);
            $pdf->Cell($pdf->pixelsToUnits(105), 4, $alarm->getEndDeviceDate() ? $alarm->getEndDeviceDate()->format('d.m.Y. H:i:s') : '', 1, 0, 'L', $fill);
            $pdf->Cell($pdf->pixelsToUnits(60), 4, $alarm->isActive() ? 'Da' : 'Ne', 1, 0, 'L', $fill);
            $pdf->Cell($pdf->pixelsToUnits(100), 4, $alarm->getLocation(), 1, 0, 'L', $fill);
            $pdf->Cell($pdf->pixelsToUnits(100), 4, $alarm->getType(), 1, 0, 'L', $fill);

            $fill = !$fill;
            $pdf->Ln();
        }

        if ($i == 1) {
            $pdf->Cell($pdf->pixelsToUnits(30 + 105 * 2 + 60 + 100 * 2), 6, 'Nema podataka!', 1, 0, 'L', false);
        }

        $this->savePDF($pdf);
    }
}