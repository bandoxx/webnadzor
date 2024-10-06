<?php

namespace App\Controller\Api;

use App\Entity\Device;
use App\Service\ChartHandler;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/api/chart/{deviceId}/{entry}', name: 'app_api_chart_getchartdata')]
class ChartController extends AbstractController
{

    public function __invoke(
        #[MapEntity(id: 'deviceId')]
        Device $device,
        int $entry,
        Request $request,
        ChartHandler $chartHandler
    ): JsonResponse
    {
        $type = $request->query->get('type');

        if (in_array($type, [ChartHandler::HUMIDITY, ChartHandler::TEMPERATURE], true) === false) {
            return $this->json('Type not valid.', Response::HTTP_BAD_REQUEST);
        }

        if ($fromDate = $request->query->get('fromDate')) {
            $fromDate = (new \DateTime())->setTimestamp((int) $fromDate);
        }

        if ($toDate = $request->query->get('toDate')) {
            $toDate = (new \DateTime())->setTimestamp((int) $toDate)->setTime(23, 59, 59);
        }

        $result = $chartHandler->createChart($device, $entry, $type, $fromDate, $toDate);

        return $this->json($result, Response::HTTP_OK);
    }


}