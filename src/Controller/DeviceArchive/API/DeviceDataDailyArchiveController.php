<?php

namespace App\Controller\DeviceArchive\API;

use App\Entity\Device;
use App\Repository\DeviceDataArchiveRepository;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\RouterInterface;

#[Route(path: '/api/device/{id}/{entry}/archive/daily', name: 'api_devicedataarchive_getdailydata', methods: 'GET')]
class DeviceDataDailyArchiveController extends AbstractController
{

    public function __invoke(
        #[MapEntity(id: 'id')]
        Device $device,
        int $entry,
        RouterInterface $router,
        DeviceDataArchiveRepository $deviceDataArchiveRepository
    ): Response
    {
        $archiveData = $deviceDataArchiveRepository->getDailyArchives($device, $entry);
        $result = [];
        $i = 0;
        foreach ($archiveData as $data) {
            $result[] = [
                ++$i,
                $data->getArchiveDate()->format('d.m.Y.'),
                $data->getServerDate()->format('d.m.Y. H:i:s'),
                sprintf('<div style="height: 3px;">&nbsp;</div><div class="actionbar"><a href="%s" class="action view"><span>Excel</span></a></div>',
                    $router->generate('api_device_data_archive_download', [
                        'id' => $data->getId(),
                        'type' => 'xlsx'
                    ])
                ),
                sprintf('<div style="height: 3px;">&nbsp;</div><div class="actionbar"><a href="%s" class="action view"><span>PDF</span></a></div>',
                    $router->generate('api_device_data_archive_download', [
                        'id' => $data->getId(),
                        'type' => 'pdf'
                    ])
                )
            ];
        }

        return $this->json([
            'data' => $result
        ], Response::HTTP_OK);
    }

}