<?php

namespace App\Controller\SMS;

use App\Repository\ClientRepository;
use App\Repository\DeviceAlarmLogRepository;
use App\Repository\SmsDeliveryReportRepository;
use App\Service\APIClient\InfobipClient;
use App\Service\Model\AlarmListSummary;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AlarmSMSReportController extends AbstractController
{

    #[Route('/admin/alarm-sms-reports', name: 'alarm_sms_reports')]
    public function __invoke(Request $request, ClientRepository $clientRepository, InfobipClient $infobipClient, SmsDeliveryReportRepository $smsDeliveryReportRepository): Response
    {
        /** @var array<AlarmListSummary> $table */
        $table = [];
        $dateFrom = $request->query->get('date_from');
        $dateTo   = $request->query->get('date_to');

        $smsReportData = $smsDeliveryReportRepository->findByDates(
            $dateFrom ? new \DateTime($dateFrom) : null,
            $dateTo ? new \DateTime($dateTo) : null
        );

        return $this->render('v2/sms/sms_report_list.html.twig', [
            'summary' => array_values($smsReportData),
            'credit' => $infobipClient->checkBalance()
        ]);
    }

}