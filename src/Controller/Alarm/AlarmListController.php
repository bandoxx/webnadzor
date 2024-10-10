<?php

namespace App\Controller\Alarm;

use App\Entity\Client;
use App\Entity\Device;
use App\Factory\DeviceOverviewFactory;
use App\Repository\DeviceAlarmRepository;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/admin/{clientId}/alarm/{id}/{entry}/list', name: 'app_alarm_list', methods: 'GET')]
class AlarmListController extends AbstractController
{

    public function __invoke(
        #[MapEntity(id: 'clientId')]
        Client $client,
        #[MapEntity(id: 'id')]
        Device $device,
        int $entry,
        DeviceAlarmRepository $deviceAlarmRepository,
        DeviceOverviewFactory $deviceOverviewFactory
    ): Response
    {
        $alarms = $deviceAlarmRepository->findByDeviceOrderByEndDate($device, $entry);
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

        return $this->render('v2/alarm/list.html.twig', [
            'alarms' => $table,
            'device' => $deviceOverviewFactory->create($device, $entry),
            'entry' => $entry,
        ]);
    }

}