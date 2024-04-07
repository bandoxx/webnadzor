<?php

namespace App\Controller\Api;

use App\Entity\DeviceDataArchive;
use App\Repository\DeviceDataArchiveRepository;
use App\Repository\DeviceDataRepository;
use App\Repository\DeviceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DeviceDataArchiveController extends AbstractController
{

    #[Route(path: '/api/device/{id}/{entry}/archive/daily', methods: 'GET', name:'app_api_devicedataarchive_getdailydata')]
    public function getDailyData($id, $entry, DeviceRepository $deviceRepository, DeviceDataArchiveRepository $deviceDataArchiveRepository): Response
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

    #[Route(path: '/api/device/{id}/{entry}/archive/monthly', methods: 'GET', name:'app_api_devicedataarchive_getmonthlydata')]
    public function getMonthlyData($id, $entry, DeviceRepository $deviceRepository, DeviceDataArchiveRepository $deviceDataArchiveRepository): Response
    {
        $device = $deviceRepository->find($id);
        $archiveData = $deviceDataArchiveRepository->getMonthlyArchives($device, $entry);
        $result = [];
        $i = 0;
        foreach ($archiveData as $data) {
            $result[] = [
                ++$i,
                $data->getArchiveDate()->format('m.Y.'),
                $data->getServerDate()->format('d.m.Y. H:i:s'),
                sprintf('<div style="height: 3px;">&nbsp;</div><div class="actionbar"><a href="%s.xlsx" class="action view"><span>Excel</span></a></div>', $data->getFilename()),
                sprintf('<div style="height: 3px;">&nbsp;</div><div class="actionbar"><a href="%s.pdf" class="action view"><span>PDF</span></a></div>', $data->getFilename())
            ];
        }

        return $this->json([
            'data' => $result
        ], Response::HTTP_OK);
    }}