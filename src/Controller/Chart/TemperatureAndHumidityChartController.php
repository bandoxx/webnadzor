<?php

namespace App\Controller\Chart;

use App\Entity\Device;
use App\Service\Chart\ChartHandler;
use App\Service\Chart\Type\DeviceData\HumidityType;
use App\Service\Chart\Type\DeviceData\TemperatureType;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/api/chart/{deviceId}/{entry}/t-rh', name: 'api_temperature_and_humidity_chart')]
class TemperatureAndHumidityChartController extends AbstractController
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

        if (in_array($type, [HumidityType::KEY, TemperatureType::TYPE], true) === false) {
            return $this->json('Type not valid.', Response::HTTP_BAD_REQUEST);
        }

        if ($fromDate = $request->query->get('fromDate')) {
            $fromDate = (new \DateTime())->setTimestamp((int) $fromDate);
        }

        if ($toDate = $request->query->get('toDate')) {
            $toDate = (new \DateTime())->setTimestamp((int) $toDate)->setTime(23, 59, 59);
        }

        $result = $chartHandler->createDeviceDataChart($device, $type, $entry, $fromDate, $toDate);

        return $this->json($result, Response::HTTP_OK);
    }


}