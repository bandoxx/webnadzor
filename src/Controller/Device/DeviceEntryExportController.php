<?php

namespace App\Controller\Device;

use App\Repository\ClientRepository;
use App\Repository\DeviceDataRepository;
use App\Repository\DeviceRepository;
use App\Service\Archiver\PDFArchiver;
use App\Service\Archiver\XLSXArchiver;
use App\Service\DeviceDataFormatter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/admin/{clientId}/device/{id}/{entry}/export', name: 'app_device_export')]
class DeviceEntryExportController extends AbstractController
{
    public function __invoke($clientId, $id, $entry, Request $request, DeviceRepository $deviceRepository, DeviceDataRepository $deviceDataRepository, DeviceDataFormatter $deviceDataFormatter, PDFArchiver $PDFArchiver, XLSXArchiver $XLSXArchiver)
    {
        if ($request->get('date_from')) {
            $dateFrom = (new \DateTime($request->get('date_from')));
        } else {
            $dateFrom = (new \DateTime());
        }

        $dateFrom->setTime(0, 0);

        if ($request->get('date_to')) {
            $dateTo = (new \DateTime($request->get('date_to')));
        } else {
            $dateTo = (new \DateTime());
        }

        $dateTo->modify('+1 day')->setTime(0, 0);

        $device = $deviceRepository->find($id);
        $data = $deviceDataRepository->findByDeviceAndBetweenDates($device, $dateFrom, $dateTo);

        if ($export = $request->get('export')) {
            if ($export === 'xlsx') {
                $response = new StreamedResponse(
                    function () use ($XLSXArchiver, $device, $data, $entry, $dateFrom, $dateTo) {
                        $XLSXArchiver->saveCustom($device, $data, $entry, $dateFrom, $dateTo);
                    }
                );

                $response->headers->set('Content-Type', 'application/vnd.ms-excel');
                $response->headers->set('Content-Disposition', 'attachment;filename="ExportScan.xlsx"');
            } else if ($export === 'pdf') {
                $response = new StreamedResponse(
                    function () use ($PDFArchiver, $device, $data, $entry, $dateFrom, $dateTo) {
                        $PDFArchiver->saveCustom($device, $data, $entry, $dateFrom, $dateTo);
                    }
                );

                $response->headers->set('Content-Type', 'application/pdf');
                $response->headers->set('Content-Disposition', 'attachment;filename="ExportScan.pdf"');
            } else {
                throw new BadRequestException("Export type doesn't exists!");
            }

            $response->headers->set('Cache-Control','max-age=0');

            return $response;
        }

        $tableData = $deviceDataFormatter->getTable($device, $data, $entry);

        return $this->render('device/device_sensor_export.html.twig',[
            'device' => $device,
            'table_data' => $tableData,
            'entry' => $entry,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'client_id' => $clientId
        ]);

    }

}