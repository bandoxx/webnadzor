<?php

namespace App\Controller\Api;

use App\Model\ChartType;
use App\Repository\DeviceDataRepository;
use App\Service\ChartHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/api/chart/{deviceId}/{entry}', name: 'app_api_chart_getchartdata')]
class ChartController extends AbstractController
{

    public function __invoke(int $deviceId, int $entry, DeviceDataRepository $deviceDataRepository, Request $request, ChartHandler $chartHandler): JsonResponse
    {
        if ($fromDate = $request->query->get('fromDate')) {
            $fromDate = (new \DateTime())->setTimestamp((int) $fromDate);
        }

        if ($toDate = $request->query->get('toDate')) {
            $toDate = (new \DateTime())->setTimestamp((int) $toDate);
        }

        $type = $request->query->get('type');

        $data = $deviceDataRepository->getChartData(
            $deviceId,
            $fromDate,
            $toDate
        );

        $result = $chartHandler->createChart($type, $data, $deviceId, $entry);

        return $this->json($result, Response::HTTP_OK);
    }


}