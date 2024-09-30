<?php

namespace App\Controller\Device;

use App\Factory\DeviceOverviewFactory;
use App\Repository\ClientRepository;
use App\Repository\DeviceDataRepository;
use App\Repository\DeviceRepository;
use App\Service\Archiver\DeviceData\DeviceDataPDFArchiver;
use App\Service\Archiver\DeviceData\DeviceDataXLSXArchiver;
use App\Service\DeviceDataFormatter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route(path: '/admin/{clientId}/device/{id}/{entry}/export', name: 'app_device_export', methods: 'GET|POST')]
class DeviceEntryExportController extends AbstractController
{
    public function __invoke(int $clientId, int $id, int $entry, SluggerInterface $slugger, ClientRepository $clientRepository, Request $request, DeviceRepository $deviceRepository, DeviceDataRepository $deviceDataRepository, DeviceDataFormatter $deviceDataFormatter, DeviceDataPDFArchiver $PDFArchiver, DeviceDataXLSXArchiver $XLSXArchiver, DeviceOverviewFactory $deviceOverviewFactory): StreamedResponse|Response|NotFoundHttpException
    {
        $dateFrom = new \DateTime($request->get('date_from'));
        $dateFrom->setTime(0, 0);

        $dateTo = (new \DateTime($request->get('date_to')));
        $dateTo->setTime(23, 59);

        $device = $deviceRepository->find($id);
        if (!$device) {
            return $this->createNotFoundException('Unable to find Device entity.');
        }

        $data = $deviceDataRepository->findByDeviceAndBetweenDates($device, $dateFrom, $dateTo);

        if ($export = $request->get('export')) {
            $fileName = sprintf('export_%s_%s_%s',
                $slugger->slug($device->getXmlName()),
                $dateFrom->format('d-m-Y'),
                $dateTo->format('d-m-Y'),
            );

            if ($export === 'xlsx') {
                $response = new StreamedResponse(
                    function () use ($XLSXArchiver, $device, $data, $entry, $dateFrom, $dateTo) {
                        $XLSXArchiver->saveCustom($device, $data, $entry, $dateFrom, $dateTo);
                    }
                );

                $response->headers->set('Content-Type', 'application/vnd.ms-excel');
                $response->headers->set('Content-Disposition', sprintf('attachment;filename="%s.xlsx"', $fileName));
            } else if ($export === 'pdf') {
                $response = new StreamedResponse(
                    function () use ($PDFArchiver, $device, $data, $entry, $dateFrom, $dateTo) {
                        $PDFArchiver->saveCustom($device, $data, $entry, $dateFrom, $dateTo);
                    }
                );
            } else {
                throw new BadRequestException("Export type doesn't exists!");
            }

            return $response;
        }

        $tableData = $deviceDataFormatter->getTable($device, $data, $entry);

        return $this->render('v2/device/device_sensor_export.html.twig',[
            'device' => $deviceOverviewFactory->create($device, $entry),
            'table_data' => $tableData,
            'entry' => $entry,
            'date_from' => $dateFrom,
            'date_to' => $dateTo
        ]);

    }

}