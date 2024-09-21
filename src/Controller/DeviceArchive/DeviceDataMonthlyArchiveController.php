<?php

namespace App\Controller\DeviceArchive;

use App\Repository\DeviceDataArchiveRepository;
use App\Repository\DeviceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route(path: '/admin/{clientId}/device/{deviceId}/{entry}/archive/monthly', name: 'app_devicedataarchive_getmonthlydata', methods: 'GET')]
class DeviceDataMonthlyArchiveController extends AbstractController
{

    // TODO: Check client and device id
    public function __invoke(int $clientId, int $deviceId, int $entry, DeviceRepository $deviceRepository, DeviceDataArchiveRepository $deviceDataArchiveRepository, UrlGeneratorInterface $router): Response
    {
        $device = $deviceRepository->find($deviceId);
        if (!$device) {
            throw new NotFoundHttpException('Device not found.');
        }

        $archiveData = $deviceDataArchiveRepository->getMonthlyArchives($device, $entry);
        $result = [];
        $i = 0;
        foreach ($archiveData as $data) {
            $result[] = [
                'row' => ++$i,
                'archive_date' => $data->getArchiveDate()->format('m.Y.'),
                'server_date' => $data->getServerDate()->format('d.m.Y. H:i:s'),
                'xlsx_path' => $router->generate('api_device_data_archive_download', [
                    'id' => $data->getId(),
                    'type' => 'xlsx'
                ]),
                'pdf_path' => $router->generate('api_device_data_archive_download', [
                    'id' => $data->getId(),
                    'type' => 'pdf'
                ]),
                'raw_data_path' => $router->generate('api_device_data_archive_download', [
                    'id' => $data->getId(),
                    'type' => 'enc'
                ])
            ];
        }

        return $this->render('v2/device/device_sensor_archive_monthly.html.twig', [
            'data' => $result,
            'device' => $device,
            'entry' => $entry
        ]);
    }

}