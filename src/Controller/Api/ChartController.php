<?php

namespace App\Controller\Api;

use App\Model\ChartType;
use App\Repository\DeviceDataRepository;
use App\Service\ChartHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ChartController extends AbstractController
{

    #[Route(path: '/api/chart/{deviceId}/{entry}', name: 'app_api_chart_getchartdata')]
    public function getChartData($deviceId, $entry, DeviceDataRepository $deviceDataRepository, Request $request, ChartHandler $chartHandler)
    {
        if ($fromDate = $request->query->get('fromDate')) {
            $fromDate = (new \DateTime())->setTimestamp($fromDate);
        }

        if ($toDate = $request->query->get('toDate')) {
            $toDate = (new \DateTime())->setTimestamp($toDate);
        }

        $type = $request->query->get('type');

        $data = $deviceDataRepository->getChartData(
            $deviceId,
            $fromDate,
            $toDate
        );

        $result = $chartHandler->createChart($type, $data, $entry);

        return $this->json($result, Response::HTTP_OK);
    }


}