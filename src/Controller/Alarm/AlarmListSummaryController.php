<?php

namespace App\Controller\Alarm;

use App\Repository\ClientRepository;
use App\Repository\DeviceAlarmLogRepository;
use App\Service\APIClient\InfobipClient;
use App\Service\Model\AlarmListSummary;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AlarmListSummaryController extends AbstractController
{

    #[Route('/admin/alarm-list-summary', name: 'app_alarm_list_summary')]
    public function __invoke(Request $request, ClientRepository $clientRepository, InfobipClient $infobipClient, DeviceAlarmLogRepository $deviceAlarmLogRepository): Response
    {
        /** @var array<AlarmListSummary> $table */
        $table = [];
        $dateFrom = $request->query->get('date_from');
        $dateTo   = $request->query->get('date_to');

        $clients = $clientRepository->findAllActive();

        foreach ($clients as $client) {
            $clientId = $client->getId();

            $summary = $deviceAlarmLogRepository->findByDates(
                $client,
                $dateFrom ? new \DateTime($dateFrom) : null,
                $dateTo ? new \DateTime($dateTo) : null
            );

            if (isset($table[$clientId]) === false) {
                $table[$clientId] = new AlarmListSummary($client->getName());
            }

            foreach ($summary as $item) {
                $table[$clientId]->add($item->getNotifiedBy());
            }
        }


        return $this->render('v2/alarm/phone_alarm_list.html.twig', [
            'summary' => array_values($table),
            'credit' => $infobipClient->checkBalance()
        ]);
    }

}