<?php

namespace App\Controller\Alarm;

use App\Repository\DeviceAlarmRepository;
use App\Repository\DeviceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/admin/{clientId}/alarm/{id}/list', name: 'app_alarm_list', methods: 'GET')]
class AlarmListController extends AbstractController
{

    public function __invoke(int $clientId, int $id, DeviceRepository $deviceRepository, DeviceAlarmRepository $deviceAlarmRepository): Response
    {
        $device = $deviceRepository->find($id);

        if (!$device) {
            throw $this->createNotFoundException();
        }

        $alarms = $deviceAlarmRepository->findByDevice($device);
        $table = [];

        foreach ($alarms as $alarm) {
            $table[] = [
                'date' => $alarm->getDeviceDate(),
                'end_date' => $alarm->getEndDeviceDate(),
                'active' => $alarm->isActive(),
                'place' => $alarm->getLocation(),
                'type' => $alarm->getType()
            ];
        }

        return $this->render('alarm/list.html.twig', [
            'alarms' => $table,
            'device' => $device
        ]);
    }

}