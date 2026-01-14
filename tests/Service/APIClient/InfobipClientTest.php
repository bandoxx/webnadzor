<?php

namespace App\Tests\Service\APIClient;

use App\Entity\SmsDeliveryReport;
use App\Factory\SmsDeliveryFactory;
use App\Service\APIClient\InfobipClient;
use App\Service\Model\InfoBipClient\AccountBalance;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class InfobipClientTest extends TestCase
{
    private SmsDeliveryFactory&MockObject $smsDeliveryFactory;
    private HttpClientInterface&MockObject $httpClient;

    protected function setUp(): void
    {
        $this->smsDeliveryFactory = $this->createMock(SmsDeliveryFactory::class);
        $this->httpClient = $this->createMock(HttpClientInterface::class);
    }

    // ==================== sendMessage Tests ====================

    public function testSendMessageDoesNothingWhenClientInactive(): void
    {
        $client = new InfobipClient(
            $this->smsDeliveryFactory,
            $this->httpClient,
            'sender',
            false
        );

        $this->httpClient->expects($this->never())->method('request');

        $client->sendMessage(['+385912345678'], 'Test message');
    }

    public function testSendMessageDoesNothingWhenPhoneNumbersEmpty(): void
    {
        $client = new InfobipClient(
            $this->smsDeliveryFactory,
            $this->httpClient,
            'sender',
            true
        );

        $this->httpClient->expects($this->never())->method('request');

        $client->sendMessage([], 'Test message');
    }

    public function testSendMessageSendsRequestWithCorrectPayload(): void
    {
        $client = new InfobipClient(
            $this->smsDeliveryFactory,
            $this->httpClient,
            'TestSender',
            true
        );

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                '/sms/3/messages',
                $this->callback(function ($options) {
                    $body = json_decode($options['body'], true);

                    return $body['messages']['sender'] === 'Intelteh'
                        && $body['messages']['from'] === 'TestSender'
                        && $body['messages']['content']['text'] === 'Hello World'
                        && count($body['messages']['destinations']) === 1
                        && $body['messages']['destinations'][0]['to'] === '+385912345678';
                })
            );

        $client->sendMessage(['+385912345678'], 'Hello World');
    }

    public function testSendMessageSupportsMultipleRecipients(): void
    {
        $client = new InfobipClient(
            $this->smsDeliveryFactory,
            $this->httpClient,
            'sender',
            true
        );

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                '/sms/3/messages',
                $this->callback(function ($options) {
                    $body = json_decode($options['body'], true);
                    $destinations = $body['messages']['destinations'];

                    return count($destinations) === 3
                        && $destinations[0]['to'] === '+385912345678'
                        && $destinations[1]['to'] === '+385998877665'
                        && $destinations[2]['to'] === '+385991234567';
                })
            );

        $client->sendMessage(
            ['+385912345678', '+385998877665', '+385991234567'],
            'Message to multiple recipients'
        );
    }

    // ==================== checkBalance Tests ====================

    public function testCheckBalanceReturnsAccountBalance(): void
    {
        $client = new InfobipClient(
            $this->smsDeliveryFactory,
            $this->httpClient,
            'sender',
            true
        );

        $response = $this->createMock(ResponseInterface::class);
        $response->method('toArray')->willReturn([
            'balance' => 125.50,
            'currency' => 'EUR',
        ]);

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with('GET', '/account/1/balance')
            ->willReturn($response);

        $result = $client->checkBalance();

        $this->assertInstanceOf(AccountBalance::class, $result);
        $this->assertEquals(125.50, $result->balance);
        $this->assertEquals('EUR', $result->currency);
    }

    // ==================== getSMSReports Tests ====================

    public function testGetSMSReportsReturnsDeliveryReports(): void
    {
        $client = new InfobipClient(
            $this->smsDeliveryFactory,
            $this->httpClient,
            'sender',
            true
        );

        $response = $this->createMock(ResponseInterface::class);
        $response->method('toArray')->willReturn([
            'results' => [
                [
                    'messageId' => 'msg-123',
                    'to' => '+385912345678',
                    'status' => [
                        'description' => 'Delivered',
                        'name' => 'DELIVERED',
                    ],
                    'error' => [
                        'description' => 'No error',
                        'name' => 'NO_ERROR',
                    ],
                    'sentAt' => '2024-01-15T10:30:00.000+0000',
                ],
                [
                    'messageId' => 'msg-456',
                    'to' => '+385998877665',
                    'status' => [
                        'description' => 'Pending',
                        'name' => 'PENDING',
                    ],
                    'error' => [
                        'description' => 'No error',
                        'name' => 'NO_ERROR',
                    ],
                    'sentAt' => '2024-01-15T10:31:00.000+0000',
                ],
            ],
        ]);

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with('GET', '/sms/3/reports')
            ->willReturn($response);

        $report1 = $this->createMock(SmsDeliveryReport::class);
        $report2 = $this->createMock(SmsDeliveryReport::class);

        $this->smsDeliveryFactory
            ->expects($this->exactly(2))
            ->method('create')
            ->willReturnOnConsecutiveCalls($report1, $report2);

        $result = $client->getSMSReports();

        $this->assertCount(2, $result);
        $this->assertSame($report1, $result[0]);
        $this->assertSame($report2, $result[1]);
    }

    public function testGetSMSReportsReturnsEmptyArrayWhenNoReports(): void
    {
        $client = new InfobipClient(
            $this->smsDeliveryFactory,
            $this->httpClient,
            'sender',
            true
        );

        $response = $this->createMock(ResponseInterface::class);
        $response->method('toArray')->willReturn(['results' => []]);

        $this->httpClient
            ->method('request')
            ->willReturn($response);

        $this->smsDeliveryFactory
            ->expects($this->never())
            ->method('create');

        $result = $client->getSMSReports();

        $this->assertEmpty($result);
    }

    public function testGetSMSReportsPassesCorrectDataToFactory(): void
    {
        $client = new InfobipClient(
            $this->smsDeliveryFactory,
            $this->httpClient,
            'sender',
            true
        );

        $response = $this->createMock(ResponseInterface::class);
        $response->method('toArray')->willReturn([
            'results' => [
                [
                    'messageId' => 'test-msg-id',
                    'to' => '+385912345678',
                    'status' => [
                        'description' => 'Message delivered',
                        'name' => 'DELIVERED',
                    ],
                    'error' => [
                        'description' => 'No error occurred',
                        'name' => 'NO_ERROR',
                    ],
                    'sentAt' => '2024-01-15T12:00:00.000+0000',
                ],
            ],
        ]);

        $this->httpClient
            ->method('request')
            ->willReturn($response);

        $this->smsDeliveryFactory
            ->expects($this->once())
            ->method('create')
            ->with(
                'test-msg-id',
                '+385912345678',
                'Message delivered',
                'DELIVERED',
                'No error occurred',
                'NO_ERROR',
                '2024-01-15T12:00:00.000+0000'
            )
            ->willReturn($this->createMock(SmsDeliveryReport::class));

        $client->getSMSReports();
    }
}
