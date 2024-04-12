<?php

namespace App\Controller\Alarm;

use App\Repository\DeviceAlarmRepository;
use App\Repository\DeviceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/admin/{clientId}/alarm/{id}/list', methods: 'GET', name: 'app_alarm_list')]
class AlarmListController extends AbstractController
{

    public function __invoke($clientId, $id, DeviceRepository $deviceRepository, DeviceAlarmRepository $deviceAlarmRepository)
    {
        $device = $deviceRepository->find($id);

        if (!$device) {
            throw $this->createNotFoundException();
        }

        return $this->render('alarm/list.html.twig', [
            'alarms' => $deviceAlarmRepository->findByDevice($device),
            'device' => $device
        ]);
    }

}