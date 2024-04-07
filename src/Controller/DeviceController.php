<?php

namespace App\Controller;

use App\Entity\Device;
use App\Entity\DeviceData;
use App\Entity\User;
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

class DeviceController extends AbstractController
{

    #[Route(path: '/device/{id}/edit', methods: 'GET|POST', name: 'app_device_edit')]
    public function edit($id, Request $request, DeviceRepository $deviceRepository, DeviceIconRepository $deviceIconRepository, DeviceUpdater $deviceUpdater): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $client = $user->getClient();
        $error = [];

        $device = $deviceRepository->find($id);
        $icons = $deviceIconRepository->findBy(['client' => $client]);
        if ($request->getMethod() === 'POST') {
            try {
                $device = $deviceUpdater->update($device, $request->request->all());
            } catch (\Throwable $e) {
                $error = [$e->getMessage()];
            }
        }

        if ($error) {
            dd($error);
        }
        return $this->render('device/edit.html.twig', [
            'device' => $device,
            'icons' => $icons,
            'errors' => $error
        ]);
    }

    #[Route(path: '/device/{id}/toggle-parser', methods: 'GET', name: 'app_device_toggledeviceparser')]
    public function toggleDeviceParser($id, DeviceRepository $deviceRepository, EntityManagerInterface $entityManager): RedirectResponse
    {
        $device = $deviceRepository->find($id);

        if (!$device) {
            throw new BadRequestException("Device doesn't exists.");
        }

        $device->setParserActive(!$device->isParserActive());

        $entityManager->flush();

        return $this->redirectToRoute('app_device_edit', ['id' => $device->getId()]);
    }

    #[Route(path: '/device/{id}/{entry}/show', methods: 'GET', name: 'app_device_entry_show')]
    public function showEntry($id, $entry, DeviceRepository $deviceRepository, DeviceDataRepository $deviceDataRepository): Response
    {
        $device = $deviceRepository->find($id);
        $deviceData = $deviceDataRepository->findLastRecordForDeviceAndEntry($device, $entry);

        return $this->render('device/device_sensor_show.html.twig', [
            'device' => $device,
            'device_data' => $deviceData,
            'entry' => $entry
        ]);
    }

    #[Route(path: '/device/{id}/show', methods: 'GET', name: 'app_device_show')]
    public function show($id, DeviceRepository $deviceRepository): Response
    {
        $device = $deviceRepository->find($id);

        return $this->render('device/edit.html.twig', [
            'device' => $device
        ]);
    }

    #[Route(path: '/device/{id}/{entry}/archive', name: 'app_devicedataarchive_read')]
    public function read($id, $entry, DeviceRepository $deviceRepository, DeviceDataRepository $deviceDataRepository): Response
    {
        $device = $deviceRepository->find($id);
        $deviceData = $deviceDataRepository->findLastRecordForDeviceAndEntry($device, $entry);

        return $this->render('device/device_sensor_archive.html.twig',[
            'device' => $device,
            'device_data' => $deviceData,
            'entry' => $entry
        ]);
    }

    #[Route(path: '/device/{id}/{entry}/export', name: 'app_device_export')]
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