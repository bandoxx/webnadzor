<?php

namespace App\Controller\Alarm;

use App\Entity\Client;
use App\Repository\DeviceAlarmLogRepository;
use App\Service\Model\AlarmListSummary;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AlarmListSummaryController extends AbstractController
{

    #[Route('/admin/alarm-list-summary', name: 'app_alarm_list_summary')]
    public function __invoke(Request $request, DeviceAlarmLogRepository $deviceAlarmLogRepository): Response
    {
        $dateFrom = $request->query->get('dateFrom');
        $dateTo   = $request->query->get('dateTo');

        if ($dateFrom && $dateTo) {
            $summary = $deviceAlarmLogRepository->findByDates(
                new \DateTime($dateFrom),
                new \DateTime($dateTo)
            );
        } else {
            $summary = $deviceAlarmLogRepository->findAll();
        }

        $table = [];

        foreach ($summary as $item) {
            /** @var Client $client */
            $client = $item->getClient();
            $clientId = $client->getId();

            if (isset($table[$clientId]) === false) {
                $table[$clientId] = new AlarmListSummary($client->getName());
            }

            $table[$clientId]->add($item->getNotifiedBy());
        }

        return $this->render('v2/alarm/phone_alarm_list.html.twig', [
            'table' => array_values($table)
        ]);
    }

}