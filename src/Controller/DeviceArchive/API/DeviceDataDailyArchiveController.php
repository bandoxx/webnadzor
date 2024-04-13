<?php

namespace App\Controller\DeviceArchive\API;

use App\Repository\DeviceDataArchiveRepository;
use App\Repository\DeviceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/api/device/{id}/{entry}/archive/daily', name: 'api_devicedataarchive_getdailydata', methods: 'GET')]
class DeviceDataDailyArchiveController extends AbstractController
{

    public function __invoke($id, $entry, DeviceRepository $deviceRepository, DeviceDataArchiveRepository $deviceDataArchiveRepository): Response
    {
        $device = $deviceRepository->find($id);
        $archiveData = $deviceDataArchiveRepository->getDailyArchives($device, $entry);
        $result = [];
        $i = 0;
        foreach ($archiveData as $data) {
            $result[] = [
                ++$i,
                $data->getArchiveDate()->format('d.m.Y.'),
                $data->getServerDate()->format('d.m.Y. H:i:s'),
                sprintf('<div style="height: 3px;">&nbsp;</div><div class="actionbar"><a href="%s.xlsx" class="action view"><span>Excel</span></a></div>', $data->getFilename()),
                sprintf('<div style="height: 3px;">&nbsp;</div><div class="actionbar"><a href="%s.pdf" class="action view"><span>PDF</span></a></div>', $data->getFilename())
            ];
        }

        return $this->json([
            'data' => $result
        ], Response::HTTP_OK);
    }

}