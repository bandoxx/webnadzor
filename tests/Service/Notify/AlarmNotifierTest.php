<?php

namespace App\Tests\Service\Notify;

use App\Entity\Client;
use App\Entity\ClientSetting;
use App\Entity\Device;
use App\Entity\DeviceAlarm;
use App\Repository\DeviceAlarmRepository;
use App\Repository\DeviceRepository;
use App\Service\Alarm\AlarmLog\AlarmLogFactory;
use App\Service\Alarm\AlarmRecipients;
use App\Service\Alarm\Types\DeviceSupplyOff;
use App\Service\Alarm\Types\TemperatureHigh;
use App\Service\APIClient\InfobipClient;
use App\Service\Mailer;
use App\Service\Notify\AlarmNotifier;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

class AlarmNotifierTest extends TestCase
{
    private Mailer&MockObject $mailer;
    private EntityManagerInterface&MockObject $entityManager;
    private InfobipClient&MockObject $infobipClient;
    private AlarmRecipients&MockObject $alarmRecipients;
    private Environment&MockObject $twig;
    private AlarmLogFactory&MockObject $alarmLogFactory;
    private LoggerInterface&MockObject $logger;
    private DeviceRepository&MockObject $deviceRepository;
    private DeviceAlarmRepository&MockObject $alarmRepository;
    private AlarmNotifier $notifier;

    protected function setUp(): void
    {
        $this->mailer = $this->createMock(Mailer::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->infobipClient = $this->createMock(InfobipClient::class);
        $this->alarmRecipients = $this->createMock(AlarmRecipients::class);
        $this->twig = $this->createMock(Environment::class);
        $this->alarmLogFactory = $this->createMock(AlarmLogFactory::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->deviceRepository = $this->createMock(DeviceRepository::class);
        $this->alarmRepository = $this->createMock(DeviceAlarmRepository::class);

        $this->entityManager
            ->method('getRepository')
            ->willReturnCallback(function ($class) {
                if ($class === Device::class) {
                    return $this->deviceRepository;
                }
                if ($class === DeviceAlarm::class) {
                    return $this->alarmRepository;
                }
                return null;
            });

        $this->notifier = new AlarmNotifier(
            $this->mailer,
            $this->entityManager,
            $this->infobipClient,
            $this->alarmRecipients,
            $this->twig,
            $this->alarmLogFactory,
            $this->logger
        );
    }

    public function testNotifyProcessesAllDevices(): void
    {
        $device1 = $this->createMockDevice(1);
        $device2 = $this->createMockDevice(2);

        $this->deviceRepository
            ->expects($this->once())
            ->method('findAll')
            ->willReturn([$device1, $device2]);

        $this->alarmRepository
            ->method('findAlarmsThatNeedsNotification')
            ->willReturn([]);

        $this->notifier->notify();
    }

    public function testNotifySkipsDevicesWithNoAlarms(): void
    {
        $device = $this->createMockDevice(1);

        $this->deviceRepository->method('findAll')->willReturn([$device]);
        $this->alarmRepository->method('findAlarmsThatNeedsNotification')->willReturn([]);

        $this->mailer->expects($this->never())->method('send');
        $this->infobipClient->expects($this->never())->method('sendMessage');

        $this->notifier->notify();
    }

    public function testNotifyBySmsSkipsAlarmsWithNoRecipients(): void
    {
        $alarm1 = $this->createMockAlarm(1, 'Message 1');
        $alarm2 = $this->createMockAlarm(2, 'Message 2');

        $this->alarmRecipients
            ->method('getRecipientsForSms')
            ->willReturnOnConsecutiveCalls([], ['385912345678']);

        $this->infobipClient
            ->expects($this->once())
            ->method('sendMessage')
            ->with(['385912345678'], $this->anything());

        $this->notifier->notifyBySMS([$alarm1, $alarm2]);
    }

    public function testNotifyBySmsProcessesAllAlarmsWithContinue(): void
    {
        $alarm1 = $this->createMockAlarm(1, 'Message 1');
        $alarm2 = $this->createMockAlarm(2, 'Message 2');
        $alarm3 = $this->createMockAlarm(3, 'Message 3');

        $this->alarmRecipients
            ->method('getRecipientsForSms')
            ->willReturnOnConsecutiveCalls(
                [],
                ['385912345678'],
                ['385998877665']
            );

        $this->infobipClient
            ->expects($this->exactly(2))
            ->method('sendMessage');

        $this->notifier->notifyBySMS([$alarm1, $alarm2, $alarm3]);
    }

    public function testNotifySendsSmsWithConvertedMessage(): void
    {
        $alarm = $this->createMockAlarm(1, 'Test čćžšđ');

        $this->alarmRecipients
            ->method('getRecipientsForSms')
            ->willReturn(['385912345678']);

        $this->infobipClient
            ->expects($this->once())
            ->method('sendMessage')
            ->with(
                ['385912345678'],
                $this->callback(function ($message) {
                    return str_contains($message, 'Intelteh D.O.O');
                })
            );

        $this->notifier->notifyBySMS([$alarm]);
    }

    public function testNotifyMarksAlarmsAsNotified(): void
    {
        $device = $this->createMockDevice(1);
        $alarm = $this->createMockAlarm(1, 'Test');

        $this->deviceRepository->method('findAll')->willReturn([$device]);
        $this->alarmRepository
            ->method('findAlarmsThatNeedsNotification')
            ->willReturnCallback(function ($d, $entry) use ($alarm) {
                return $entry === null ? [$alarm] : [];
            });

        $this->alarmRecipients->method('getRecipientsForSms')->willReturn([]);

        $alarm->expects($this->once())->method('setIsNotified')->with(true);
        $this->entityManager->expects($this->atLeastOnce())->method('flush');

        $this->notifier->notify();
    }

    public function testNotifyProcessesAllEntries(): void
    {
        $device = $this->createMockDevice(1);
        $alarmGeneral = $this->createMockAlarm(1, 'General');
        $alarmEntry1 = $this->createMockAlarm(2, 'Entry 1');
        $alarmEntry2 = $this->createMockAlarm(3, 'Entry 2');

        $this->deviceRepository->method('findAll')->willReturn([$device]);
        $this->alarmRepository
            ->method('findAlarmsThatNeedsNotification')
            ->willReturnCallback(function ($d, $entry) use ($alarmGeneral, $alarmEntry1, $alarmEntry2) {
                return match ($entry) {
                    null => [$alarmGeneral],
                    1 => [$alarmEntry1],
                    2 => [$alarmEntry2],
                    default => [],
                };
            });

        $this->alarmRecipients->method('getRecipientsForSms')->willReturn([]);

        $alarmGeneral->expects($this->once())->method('setIsNotified');
        $alarmEntry1->expects($this->once())->method('setIsNotified');
        $alarmEntry2->expects($this->once())->method('setIsNotified');

        $this->notifier->notify();
    }

    private function createMockDevice(int $id): Device
    {
        $client = $this->createMock(Client::class);
        $clientSetting = $this->createMock(ClientSetting::class);
        $clientSetting->method('getAlarmNotificationList')->willReturn([]);
        $client->method('getClientSetting')->willReturn($clientSetting);

        $device = $this->createMock(Device::class);
        $device->method('getId')->willReturn($id);
        $device->method('getName')->willReturn("Device $id");
        $device->method('getClient')->willReturn($client);
        $device->method('getApplicationEmailList')->willReturn([]);
        $device->method('getEntryData')->willReturn([]);

        return $device;
    }

    private function createMockAlarm(int $id, string $message): DeviceAlarm&MockObject
    {
        $device = $this->createMockDevice($id);

        $alarm = $this->createMock(DeviceAlarm::class);
        $alarm->method('getId')->willReturn($id);
        $alarm->method('getDevice')->willReturn($device);
        $alarm->method('getMessage')->willReturn($message);
        $alarm->method('getShortMessage')->willReturn($message);
        $alarm->method('getType')->willReturn(DeviceSupplyOff::TYPE);
        $alarm->method('getSensor')->willReturn(null);

        return $alarm;
    }
}
