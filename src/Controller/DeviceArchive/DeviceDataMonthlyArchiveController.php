<?php

namespace App\Controller\DeviceArchive;

use App\Entity\Client;
use App\Entity\Device;
use App\Factory\DeviceOverviewFactory;
use App\Repository\DeviceDataArchiveRepository;
use App\Repository\DeviceRepository;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route(path: '/admin/{clientId}/device/{deviceId}/{entry}/archive/monthly', name: 'app_devicedataarchive_getmonthlydata', methods: 'GET')]
class DeviceDataMonthlyArchiveController extends AbstractController
{
    public function __invoke(
        #[MapEntity(id: 'clientId')]
        Client $client,
        #[MapEntity(id: 'deviceId')]
        Device $device,
        Request $request,
        int $entry,
        DeviceDataArchiveRepository $deviceDataArchiveRepository,
        UrlGeneratorInterface $router,
        DeviceOverviewFactory $deviceOverviewFactory
    ): StreamedResponse|Response|NotFoundHttpException
    {
        $dateFrom = $request->query->get('date_from');
        $dateTo   = $request->query->get('date_to');

        // if missing, set defaults and redirect
        if (!$dateFrom || !$dateTo) {
            $defaults = [
                'date_from' => (new \DateTimeImmutable('-6 month'))->format('d.m.Y'),
                'date_to'   => (new \DateTimeImmutable())->format('d.m.Y'),
            ];

            return $this->redirectToRoute('app_devicedataarchive_getmonthlydata', array_merge(
                $request->query->all(),
                $defaults,
                ['deviceId' => $device->getId(), 'clientId' => $client->getId(), 'entry' => $entry],
            ));
        }

        $dateFrom = new \DateTime($dateFrom);
        $dateFrom->setTime(0, 0);
        $dateTo = (new \DateTime($dateTo));
        $dateTo->setTime(23, 59);

        $archiveData = $deviceDataArchiveRepository->getMonthlyArchives($device, $entry, $dateFrom, $dateTo);
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
            'device' => $deviceOverviewFactory->create($device, $entry),
            'entry' => $entry,
            'date_from' => $dateFrom,
            'date_to' => $dateTo
        ]);
    }

}