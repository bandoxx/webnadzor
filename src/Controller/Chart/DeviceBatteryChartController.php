<?php

namespace App\Controller\Chart;

use App\Entity\Device;
use App\Service\Chart\ChartHandler;
use App\Service\Chart\Type\Device\DeviceBatteryDataChart;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/api/chart/{deviceId}/battery', name: 'api_device_battery_chart')]
class DeviceBatteryChartController extends AbstractController
{

    public function __invoke(
        #[MapEntity(id: 'deviceId')]
        Device $device,
        Request $request,
        ChartHandler $chartHandler
    ): JsonResponse
    {
        $result = $chartHandler->createDeviceChart($device, DeviceBatteryDataChart::KEY, $request->query->get('limit', 20));

        return $this->json($result, Response::HTTP_OK);
    }

}