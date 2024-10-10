<?php

namespace App\Controller\Archive;

use App\Entity\Device;
use App\Repository\DeviceAlarmRepository;
use App\Service\Archiver\Alarm\DeviceAlarmPDFArchiver;
use App\Service\Archiver\Alarm\DeviceAlarmXLSXArchiver;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/alarm/archive/{id}/{entry}/{type}', name: 'api_device_alarm_archive_download')]
class DownloadAlarmArchiveController extends AbstractController
{

    public function __invoke(
        Device $device,
        int $entry,
        string $type,
        DeviceAlarmRepository $deviceAlarmRepository,
        DeviceAlarmPDFArchiver $deviceAlarmPDFArchiver,
        DeviceAlarmXLSXArchiver $deviceAlarmXLSXArchiver
    ): StreamedResponse|BadRequestHttpException
    {
        if (!in_array($type, ['xlsx', 'pdf'])) {
            return new BadRequestHttpException();
        }

        if ($type === 'pdf') {
            return new StreamedResponse(function () use ($deviceAlarmPDFArchiver, $deviceAlarmRepository, $device, $entry) {
                $deviceAlarmPDFArchiver->generate($device, $deviceAlarmRepository->findByDeviceOrderByEndDate($device, $entry));
            });
        }

        $response = new StreamedResponse(function () use ($deviceAlarmXLSXArchiver, $deviceAlarmRepository, $device, $entry) {
            $deviceAlarmXLSXArchiver->generate($device, $deviceAlarmRepository->findByDeviceOrderByEndDate($device, $entry));
        });

        $response->headers->set('Content-Type', 'application/vnd.ms-excel');
        $response->headers->set('Content-Disposition', sprintf('attachment;filename="%s-%s.xlsx"', $device->getName(), (new \DateTime())->format('d-m-Y')));

        return $response;
    }
}