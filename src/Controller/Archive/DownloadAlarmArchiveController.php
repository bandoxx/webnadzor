<?php

namespace App\Controller\Archive;

use App\Repository\DeviceAlarmRepository;
use App\Repository\DeviceRepository;
use App\Service\Archiver\Alarm\DeviceAlarmPDFArchiver;
use App\Service\Archiver\Alarm\DeviceAlarmXLSXArchiver;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/alarm/archive/{id}/{type}', name: 'api_device_alarm_archive_download')]
class DownloadAlarmArchiveController extends AbstractController
{

    public function __invoke($id, $type, $archiveDirectory, DeviceRepository $deviceRepository, DeviceAlarmRepository $deviceAlarmRepository, DeviceAlarmPDFArchiver $deviceAlarmPDFArchiver, DeviceAlarmXLSXArchiver $deviceAlarmXLSXArchiver): StreamedResponse|BadRequestHttpException
    {
        if (!in_array($type, ['xlsx', 'pdf'])) {
            return new BadRequestHttpException();
        }

        $device = $deviceRepository->find($id);

        if (!$device) {
            throw new NotFoundHttpException();
        }

        if ($type === 'pdf') {
            return new StreamedResponse(function () use ($deviceAlarmPDFArchiver, $deviceAlarmRepository, $device) {
                $deviceAlarmPDFArchiver->generate($device, $deviceAlarmRepository->findByDevice($device));
            });
        }

        $response = new StreamedResponse(function () use ($deviceAlarmXLSXArchiver, $deviceAlarmRepository, $device) {
            $deviceAlarmXLSXArchiver->generate($device, $deviceAlarmRepository->findByDevice($device));
        });

        $response->headers->set('Content-Type', 'application/vnd.ms-excel');
        $response->headers->set('Content-Disposition', sprintf('attachment;filename="%s.xlsx"', sha1(random_int(0, 10))));

        return $response;
    }
}