<?php

namespace App\Service\Archiver\DeviceData;

use App\Entity\Device;
use App\Entity\DeviceData;
use App\Factory\DeviceSimListFactory;
use App\Service\Archiver\Model\ArchiveModel;
use App\Service\Archiver\PDFArchiver;
use App\Service\Chart\ChartImageGenerator;
use TCPDF;

class DeviceDataPDFArchiver extends PDFArchiver implements DeviceDataArchiverInterface
{

    public function __construct(
        private readonly string $archiveDirectory,
        private readonly string $projectDirectory,
        private ChartImageGenerator $chartImageGenerator,
        private DeviceSimListFactory $deviceSimListFactory
    )
    {
        parent::__construct($this->archiveDirectory, $this->projectDirectory, $this->deviceSimListFactory);
    }

    public function saveCustom(Device $device, array $deviceData, $entry, \DateTime $fromDate, \DateTime $toDate, ?string $fileName = null): void
    {
        $subtitle = sprintf("Podaci od %s do %s", $fromDate->format(self::DAILY_FORMAT), $toDate->format(self::DAILY_FORMAT));
        $pdf = $this->generateBody($device, $deviceData, $entry, $subtitle);

        $this->saveInMemory($pdf);
    }

    public function saveDaily(Device $device, array $deviceData, $entry, \DateTime $archiveDate, ?string $fileName): ArchiveModel
    {
        $subtitle = sprintf("Podaci za %s", $archiveDate->format(self::DAILY_FORMAT));
        $pdf = $this->generateBody($device, $deviceData, $entry, $subtitle);
        $client = $device->getClient();

        $fileName = sprintf("%s.pdf", $fileName);
        $path = sprintf('%s/%s/daily/%s/', $this->getArchiveDirectory(), $client->getId(), $archiveDate->format('Y/m/d'));

        return $this->save($pdf, $path, $fileName);
    }

    public function saveMonthly(Device $device, array $deviceData, $entry, \DateTime $archiveDate, ?string $fileName): ArchiveModel
    {
        $subtitle = sprintf("Podaci za %s", $archiveDate->format(self::MONTHLY_FORMAT));
        $pdf = $this->generateBody($device, $deviceData, $entry, $subtitle);
        $client = $device->getClient();

        $fileName = sprintf("%s.pdf", $fileName);
        $path = sprintf('%s/%s/monthly/%s/', $this->getArchiveDirectory(), $client->getId(), $archiveDate->format('Y/m/d'));

        return $this->save($pdf, $path, $fileName);
    }

    private function generateBody(Device $device, array $deviceData, int $entry, string $subtitle): TCPDF
    {
        $deviceEntryData = $device->getEntryData($entry);
        $tUnit = $deviceEntryData['t_unit'];
        $rhUnit = $deviceEntryData['rh_unit'];
        $client = $device->getClient();

        $pdf = $this->prepare();

        // set default header data
        $headerData = $subtitle . "\n";
        $headerData .= $client->getHeader();

        $logo = $client?->getPdfLogo();
        $pdf->SetHeaderData($logo ? "../../../../../public/assets/images/logo/$logo" : '', 30, sprintf('Lokacija %s, mjerno mjesto %s', $device->getName(), $deviceEntryData['t_name']), $headerData);

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
        $pdf->setLineStyle(['color' => [128, 128, 128]]);

        $pdf->setJPEGQuality(100);

        //$this->includeImage($pdf, $this->chartImageGenerator->getTemperatureImageChartPath());
        //$this->includeImage($pdf, $this->chartImageGenerator->getHumidityImageChartPath());

        // Header
        $pdf->Cell($pdf->pixelsToUnits(380), 4, '', 1, 0, 'C', true);
        $pdf->Cell($pdf->pixelsToUnits(280), 4, $deviceEntryData['t_name'], 1, 0, 'C', true);
        $pdf->Ln();
        $pdf->Cell($pdf->pixelsToUnits(30), 4, 'Br.', 1, 0, 'L', true);
        $pdf->Cell($pdf->pixelsToUnits(105), 4, 'Datum', 1, 0, 'L', true);
        $pdf->Cell($pdf->pixelsToUnits(245), 4, 'Napomena', 1, 0, 'L', true);
        $pdf->Cell($pdf->pixelsToUnits(56), 4, 'Tren', 1, 0, 'L', true);
        $pdf->Cell($pdf->pixelsToUnits(56), 4, 'Max', 1, 0, 'L', true);
        $pdf->Cell($pdf->pixelsToUnits(56), 4, 'Min', 1, 0, 'L', true);
        $pdf->Cell($pdf->pixelsToUnits(56), 4, 'MKT', 1, 0, 'L', true);
        $pdf->Cell($pdf->pixelsToUnits(56), 4, 'R. vlažnost', 1, 0, 'L', true);
        $pdf->Ln();

        $i = 1;
        $row = 0;
        $fill = true;

        $pdf->SetFillColor(238, 238, 238);

        /** @var DeviceData $data */
        foreach ($deviceData as $data) {
            if (!$data->getT($entry) && !$data->getRh($entry)) {
                continue;
            }

            $i++;
            $pdf->Cell($pdf->pixelsToUnits(30), 4, ++$row, 1, 0, 'L', $fill);
            $pdf->Cell($pdf->pixelsToUnits(105), 4, $data->getDeviceDate()->format('d.m.Y. H:i:s'), 1, 0, 'L', $fill);
            $pdf->Cell($pdf->pixelsToUnits(245), 4, $data->getNote($entry), 1, 0, 'L', $fill);

            if ($data->isTemperatureOutOfRange($entry)) {
                $pdf->SetTextColor(255, 0, 0);
            }

            $pdf->Cell($pdf->pixelsToUnits(56), 4, sprintf('%s %s', $data->getT($entry), $tUnit), 1, 0, 'L', $fill);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->Cell($pdf->pixelsToUnits(56), 4, sprintf('%s %s', $data->getTMax($entry), $tUnit), 1, 0, 'L', $fill);
            $pdf->Cell($pdf->pixelsToUnits(56), 4, sprintf('%s %s', $data->getTMin($entry), $tUnit), 1, 0, 'L', $fill);
            $pdf->Cell($pdf->pixelsToUnits(56), 4, sprintf('%s %s', $data->getTAvrg($entry), $tUnit), 1, 0, 'L', $fill);

            if ($data->isHumidityOutOfRange($entry)) {
                $pdf->SetTextColor(255, 0, 0);
            }

            $pdf->Cell($pdf->pixelsToUnits(56), 4, sprintf('%s %s', $data->getRh($entry), $rhUnit), 1, 0, 'L', $fill);
            $pdf->SetTextColor(0, 0, 0);

            $fill = !$fill;
            $pdf->Ln();
        }

        if ($i === 1) {
            $pdf->Cell($pdf->pixelsToUnits(135 + 245 + 56 * 5 /** inputs */), 6, 'Nema podataka!', 1, 0, 'L', false);
        }

        return $pdf;
    }

    private function includeImage(TCPDF $pdf, string $imagePath): void
    {
        if (file_exists($imagePath)) {
            $imageWidth = 180;
            $imageHeight = 120;

            $pdf->Cell($pdf->pixelsToUnits($imageWidth), $imageHeight, '', 0, 0, 'C');
            $pdf->Image($imagePath, 10, $pdf->GetY(), $imageWidth, $imageHeight, 'JPG');

            $pdf->Ln($imageHeight + 5);
            unlink($imagePath);
        }
    }
}