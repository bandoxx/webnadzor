<?php

namespace App\Service\APIClient;


use App\Entity\SmsDeliveryReport;
use App\Factory\SmsDeliveryFactory;
use App\Service\Model\InfoBipClient\AccountBalance;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class InfobipClient
{

    public function __construct(private SmsDeliveryFactory $SMSDeliveryFactory, private HttpClientInterface $infobipClient, private string $sender, private bool $isClientActive)
    {}

    public function sendMessage(array $phoneNumbers, string $message): void
    {
        if ($this->isClientActive === false) {
            return;
        }

        if (empty($phoneNumbers)) {
            return;
        }

        $destinations = [];

        foreach ($phoneNumbers as $phoneNumber) {
            $destinations[] = ['to' => $phoneNumber];
        }

        $data = [
            'messages' => [
                'sender' => 'Intelteh', // maybe can be changeable
                'destinations' => $destinations,
                'from' => $this->sender,
                'content' => [
                    'text' => $message
                ],
            ]
        ];

        $this->infobipClient->request('POST', '/sms/3/messages', [
            'body' => json_encode($data),
        ]);
    }

    public function checkBalance(): AccountBalance
    {
        $response = $this->infobipClient->request('GET', '/account/1/balance')->toArray();

        return new AccountBalance($response['balance'], $response['currency']);
    }

    /**
     * @return array<SmsDeliveryReport>
     */
    public function getSMSReports(): array
    {
        $response = $this->infobipClient->request('GET', '/sms/3/reports')->toArray();

        $data = $response['results'];
        $reports = [];

        foreach ($data as $report) {
            $reports[] = $this->SMSDeliveryFactory->create(
                $report['messageId'],
                $report['to'],
                $report['status']['description'],
                $report['status']['name'],
                $report['error']['description'],
                $report['error']['name'],
                $report['sentAt'],
            );
        }

        return $reports;
    }

//    public function sendVoiceMessage(array $phoneNumber, string $message)
//    {
//        // TODO:
//        if ($this->isClientActive === false) {
//            return;
//        }
//
//        $data = [
//            'from' => $from,
//            'to' => $phoneNumber
//        ];
//
//        $this->infobipClient->request('POST', '/tts/3/simple', [
//            'body' => json_encode($data)
//        ]);
//    }

}