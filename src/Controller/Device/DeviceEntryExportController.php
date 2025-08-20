<?php

namespace App\Controller\Device;

use App\Entity\Client;
use App\Entity\Device;
use App\Factory\DeviceOverviewFactory;
use App\Repository\DeviceDataRepository;
use App\Service\Archiver\DeviceData\DeviceDataPDFArchiver;
use App\Service\Archiver\DeviceData\DeviceDataXLSXArchiver;
use App\Service\Chart\ChartImageGenerator;
use App\Service\DeviceDataFormatter;
use App\Service\RawData\Factory\DeviceDataRawDataFactory;
use App\Service\RawData\RawDataHandler;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route(path: '/admin/{clientId}/device/{id}/{entry}/export', name: 'app_device_export', methods: 'GET|POST')]
class DeviceEntryExportController extends AbstractController
{
    public function __invoke(
        #[MapEntity(id: 'clientId')]
        Client $client,
        #[MapEntity(id: 'id')]
        Device $device,
        int $entry,
        SluggerInterface $slugger,
        Request $request,
        DeviceDataRepository $deviceDataRepository,
        DeviceDataFormatter $deviceDataFormatter,
        DeviceDataPDFArchiver $PDFArchiver,
        DeviceDataXLSXArchiver $XLSXArchiver,
        RawDataHandler $rawDataHandler,
        DeviceDataRawDataFactory $deviceDataRawDataFactory,
        DeviceOverviewFactory $deviceOverviewFactory,
        ChartImageGenerator $chartImageGenerator
    ): StreamedResponse|Response|NotFoundHttpException
    {
        $dateFrom = new \DateTime($request->get('date_from'));
        $dateFrom->setTime(0, 0);
        $dateTo = (new \DateTime($request->get('date_to')));
        $dateTo->setTime(23, 59);

        $data = $deviceDataRepository->findByDeviceAndBetweenDates($device, $dateFrom, $dateTo);

        if ($export = $request->get('export')) {
            $baseName = $device->getDeviceIdentifier();
            $fileName = sprintf('export_%s_%s_%s',
                $slugger->slug((string) $baseName),
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
                $chartImageGenerator->generateTemperatureAndHumidityChartImage($device, $entry, $dateFrom, $dateTo);

                $response = new StreamedResponse(
                    function () use ($PDFArchiver, $device, $data, $entry, $dateFrom, $dateTo) {
                        $PDFArchiver->saveCustom($device, $data, $entry, $dateFrom, $dateTo);
                    }
                );

            } else if ($export === 'enc') {
                $path = sprintf('/tmp/%s', random_int(1, 100));
                $rawDataHandler->encrypt(
                    $deviceDataRawDataFactory->create($data, $entry, $dateFrom, $dateTo),
                    $path
                );

                $response = new BinaryFileResponse(sprintf("%s.enc", $path));
                $response->setContentDisposition(
                    ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                    sprintf("%s_%s_%s_%s.enc", $device->getName(), $entry, $dateFrom->format('d-m-Y'), $dateTo->format('d-m-Y'))
                );

                $response->deleteFileAfterSend(true);

                return $response;
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