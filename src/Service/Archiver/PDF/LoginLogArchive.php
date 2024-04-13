<?php

namespace App\Service\Archiver\PDF;

use App\Entity\Client;
use App\Entity\LoginLog;
use App\Service\Archiver\ArchiverInterface;
use TCPDF;

class LoginLogArchive implements ArchiverInterface
{
    /**
     * @param Client $client
     * @param LoginLog[] $loginLogs
     * @param \DateTime $archiveDate
     * @param string $fileName
     * @return void
     */
    public function saveDaily(Client $client, array $loginLogs, \DateTime $archiveDate, string $fileName): void
    {
        $subtitle = sprintf("Podaci za %s - Log prijava", $archiveDate->format(self::DAILY_FORMAT));
        $pdf = $this->generateBody($client, $loginLogs, $subtitle);

        $this->save($pdf, $fileName);
    }

    /**
     * @param Client $client
     * @param LoginLog[] $loginLogs
     * @return TCPDF
     */
    private function generateBody(Client $client, array $loginLogs, $subtitle) {

        // create new PDF document
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // set document information
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor(PDF_AUTHOR);
        $pdf->SetTitle(PDF_HEADER_TITLE);

        // set default header data
        $headerData = $subtitle . "\n";

        $pdf->SetHeaderData('../../../../../public/assets/images/pdflogo.png', 30, $client->getName(), $headerData);

        // set header and footer fonts
        $pdf->setHeaderFont([PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN]);
        $pdf->setFooterFont([PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA]);

        // set default monospaced font
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

        // set margins
        $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

        // set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

        // set image scale factor
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

        // add a page
        $pdf->AddPage();
        $pdf->SetFont(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA);

        // Colors, line width and bold font
        $pdf->SetFillColor(196, 202, 211);
        $pdf->SetTextColor(0,0,0);
        $pdf->SetDrawColor(0,0,0);
        $pdf->SetLineWidth(0.25);

        // Header
        $pdf->Cell($pdf->pixelsToUnits(30), 4, 'Br.', 1, 0, 'L', 1);
        $pdf->Cell($pdf->pixelsToUnits(105), 4, 'Datum', 1, 0, 'L', 1);
        $pdf->Cell($pdf->pixelsToUnits(150), 4, 'Status', 1, 0, 'L', 1);
        $pdf->Cell($pdf->pixelsToUnits(105), 4, 'Korisnicko ime', 1, 0, 'L', 1);
        $pdf->Cell($pdf->pixelsToUnits(105), 4, 'IP adresa', 1, 0, 'L', 1);
        $pdf->Cell($pdf->pixelsToUnits(105), 4, 'Preglednik', 1, 0, 'L', 1);
        $pdf->Cell($pdf->pixelsToUnits(80), 4, 'OS', 1, 0, 'L', 1);
        $pdf->Ln();

        $i = 1;
        $fill = false;
        $pdf->SetFillColor(238, 238, 238);
        foreach ($loginLogs as $log) {

            switch ($log->getStatus()) {
                case 1:
                    $status = 'Prijava u redu';
                    break;
                case 2:
                    $status = 'Nepostojeći korisnik';
                    break;
                case 3:
                    $status = 'Kriva zaporka';
                    break;
                default:
                    $status = '';
                    break;
            }

            $username = $log->getUsername();
            if (strlen($username) > 12) {
                $username = substr($username, 0, 12).'...';
            }

            $ip = @long2ip($log->getIp());


            $pdf->Cell($pdf->pixelsToUnits(30), 4, $i, 1, 0, 'L', $fill);
            $pdf->Cell($pdf->pixelsToUnits(105), 4, $log->getServerDate()?->format('d.m.Y. H:i:s'), 1, 0, 'L', $fill);
            $pdf->Cell($pdf->pixelsToUnits(150), 4, $status, 1, 0, 'L', $fill);
            $pdf->Cell($pdf->pixelsToUnits(105), 4, $username, 1, 0, 'L', $fill);
            $pdf->Cell($pdf->pixelsToUnits(105), 4, $ip, 1, 0, 'L', $fill);
            $pdf->Cell($pdf->pixelsToUnits(105), 4, $log->getBrowser(), 1, 0, 'L', $fill);
            $pdf->Cell($pdf->pixelsToUnits(80), 4, $log->getOs(), 1, 0, 'L', $fill);

            $fill = !$fill;
            $pdf->Ln();
            $i++;
        }

        if ($i == 1) {
            $pdf->Cell($pdf->pixelsToUnits(680), 6, 'Nema podataka!', 1, 0, 'L', 0);
        }

        return $pdf;
    }

    private function save(TCPDF $pdf, $fileName)
    {
        if ($fileName) {
            $pdf->Output(sprintf("%s/%s.pdf", __DIR__, $fileName), 'F');
        } else {
            $pdf->Output('php://output');
        }
    }
}