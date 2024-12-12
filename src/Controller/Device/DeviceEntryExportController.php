<?php

namespace App\Controller\Device;

use App\Entity\Client;
use App\Entity\Device;
use App\Entity\DeviceDataArchive;
use App\Factory\DeviceOverviewFactory;
use App\Repository\DeviceDataRepository;
use App\Service\Archiver\DeviceData\DeviceDataPDFArchiver;
use App\Service\Archiver\DeviceData\DeviceDataXLSXArchiver;
use App\Service\Chart\ChartHandler;
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
use Symfony\Component\Process\Process;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route(path: '/admin/{clientId}/device/{id}/{entry}/export', name: 'app_device_export', methods: 'GET|POST')]
class DeviceEntryExportController extends AbstractController
{
    private ChartHandler $chartHandler;

    public function __construct(ChartHandler $chartHandler)
    {
        $this->chartHandler = $chartHandler;
    }


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
    ): StreamedResponse|Response|NotFoundHttpException
    {
        $dateFrom = new \DateTime($request->get('date_from'));
        $dateFrom->setTime(0, 0);
        $dateTo = (new \DateTime($request->get('date_to')));
        $dateTo->setTime(23, 59);

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
                        $this->generateJsonAndChartImage($device, $entry, $dateFrom, $dateTo, 'humidity');
                        $this->generateJsonAndChartImage($device, $entry, $dateFrom, $dateTo, 'temperature');
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

    private function generateJsonAndChartImage(Device $device, $entry, \DateTime $dateFrom, \DateTime $dateThru,  $type): void
    {
        $fromTimestamp = $dateFrom->getTimestamp() * 1000;
        $toTimestamp = $dateThru->getTimestamp() * 1000;

        //generating chart image
        $chartData = $this->chartHandler->createDeviceDataChart($device, $type, $entry, $dateFrom, $dateThru);

        $plots = [];
        if (isset($chartData['min']) && is_numeric($chartData['min'])) {
            $plots[] = [
                'color' => 'white',
                'width' => 2,
                'value' => $chartData['min'],
                'label' => [
                    'style' => [
                        'color' => 'white',
                    ],
                    'text' => 'Minimum',
                    'align' => 'right',
                    'x' => -10,
                    'y' => 12,
                ],
            ];
        }

        if (isset($chartData['max']) && is_numeric($chartData['max'])) {
            $plots[] = [
                'color' => 'white',
                'width' => 2,
                'value' => $chartData['max'],
                'label' => [
                    'style' => [
                        'color' => 'white',
                    ],
                    'text' => 'Maksimum',
                    'align' => 'right',
                    'x' => -10,
                ],
            ];
        }

        if ($type === 'temperature') {
            $navigationSeries = [
                'data' => $chartData['t'],
                'name' => 'Temperatura',
            ];

            $series = [
                [
                    'name' => 'Temperatura',
                    'data' => $chartData['t'],
                ],
                [
                    'name' => 'MKT',
                    'data' => $chartData['mkt'],
                    'color' => '#e20074',
                ],
            ];
        } else {
            $navigationSeries = [
                'data' => $chartData['rh'],
                'name' => 'Vlaznost',
            ];

            $series = [
                [
                    'name' => 'Vlaznost',
                    'data' => $chartData['rh'],
                ],
            ];
        }

        //generate the final chart configuration
        $chartConfig = [
            'chart' => [
                'zoomType' => 'x',
            ],
            'title' => [
                'text' => $type === 'temperature' ? 'Temperatura' : 'Relativna vlaga',
            ],
            'time' => [
                'timezone' => 'Europe/Zagreb',
            ],
            'navigator' => [
                'adaptToUpdatedData' => false,
                'series' => $navigationSeries,
            ],
            'rangeSelector' => [
                'enabled' => false,
            ],
            'scrollbar' => [
                'liveRedraw' => false,
            ],
            'xAxis' => [
                'type' => 'datetime',
                'min' => $fromTimestamp,
                'max' => $toTimestamp,
                'dateTimeLabelFormats' => [
                    'millisecond' => '%H:%M:%S.%L',
                    'second' => '%H:%M:%S',
                    'minute' => '%H:%M',
                    'hour' => '%H:%M',
                    'day' => '%e. %b',
                    'week' => '%e. %b',
                    'month' => '%b \'%y',
                    'year' => '%Y',
                ],
            ],
            'yAxis' => [
                'plotLines' => $plots,
                'title' => [
                    'text' => '',
                    'rotation' => 0,
                ],
            ],
            'legend' => [
                'enabled' => false,
            ],
            'series' => $series,
            'tooltip' => [
                'valueDecimals' => 2,
            ],
            'dataGrouping' => [
                'enabled' => false,
            ],
        ];

        $root = $this->getParameter('kernel.project_dir');
        $jsonConfig = json_encode($chartConfig, JSON_PRETTY_PRINT);
        $jsonFilePath = sprintf("%s/archive/chartConfigData_%s.json", $root, $type);
        file_put_contents($jsonFilePath, $jsonConfig);

        //generating the image command
        $outputFilePath = sprintf("%s/archive/chart_%s.jpg", $root, $type);
        $command = ['php', 'bin/console', 'app:generate-image', $jsonFilePath, $outputFilePath];

        $process = new Process($command, $root);
        $process->mustRun();
        //remove json from archive
        unlink($jsonFilePath);
    }

}