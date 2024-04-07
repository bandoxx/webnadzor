<?php

namespace App\Controller;

use App\Entity\Device;
use App\Entity\DeviceData;
use App\Entity\User;
use App\Repository\DeviceAlarmRepository;
use App\Repository\DeviceDataRepository;
use App\Repository\DeviceIconRepository;
use App\Repository\DeviceRepository;
use App\Service\Archiver\PDFArchiver;
use App\Service\Archiver\XLSXArchiver;
use App\Service\DeviceDataFormatter;
use App\Service\DeviceUpdater;
use Doctrine\ORM\EntityManagerInterface;
use Mpdf\Tag\P;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;

class AlarmController extends AbstractController
{

    #[Route(path: '/alarm/{id}/list', methods: 'GET', name: 'app_alarm_list')]
    public function list($id, DeviceRepository $deviceRepository, DeviceAlarmRepository $deviceAlarmRepository): Response
    {
        $device = $deviceRepository->find($id);

        if (!$device) {
            throw $this->createNotFoundException();
        }

        $alarms = $deviceAlarmRepository->findByDevice($device);

        return $this->render('alarm/list.html.twig', [
            'alarms' => $alarms,
            'device' => $device
        ]);
    }

    #[Route(path: '/alarm/{id}/export', name: 'app_alarm_export')]
    public function export($id, $entry, Request $request, DeviceRepository $deviceRepository, DeviceDataRepository $deviceDataRepository, DeviceDataFormatter $deviceDataFormatter, PDFArchiver $PDFArchiver, XLSXArchiver $XLSXArchiver): Response
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

        $dateTo->setTime(23, 59);

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
            'date_to' => $dateTo
        ]);
    }
}