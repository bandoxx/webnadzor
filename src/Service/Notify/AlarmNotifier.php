<?php

namespace App\Service\Notify;

use App\Entity\Device;
use App\Entity\DeviceAlarm;
use App\Entity\DeviceAlarmLog;
use App\Repository\DeviceAlarmRepository;
use App\Service\Alarm\AlarmLog\AlarmLogFactory;
use App\Service\Alarm\AlarmRecipients;
use App\Service\Alarm\AlarmTypeGroups;
use App\Service\APIClient\InfobipClient;
use App\Service\Mailer;
use Doctrine\ORM\EntityManagerInterface;
use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

class AlarmNotifier
{
    private const array INTERNAL_RECIPIENTS = [
        'damir.cerjak@intelteh.hr',
        'petar.simic@intelteh.hr',
    ];

    private const string SENDER_EMAIL = 'rht@intelteh.hr';
    private const string BCC_LOG_EMAIL = 'logs@banox.dev';
    private const string SMS_SIGNATURE = 'Intelteh D.O.O';

    public function __construct(
        private readonly Mailer $mailer,
        private readonly EntityManagerInterface $entityManager,
        private readonly InfobipClient $infobipClient,
        private readonly AlarmRecipients $alarmRecipients,
        private readonly Environment $twig,
        private readonly AlarmLogFactory $alarmLogFactory,
        #[WithMonologChannel('mailer')]
        private readonly LoggerInterface $logger,
    ) {}

    public function notify(): void
    {
        $deviceRepository = $this->entityManager->getRepository(Device::class);
        $alarmRepository = $this->entityManager->getRepository(DeviceAlarm::class);

        $devices = $deviceRepository->findAll();

        foreach ($devices as $device) {
            $this->processDeviceAlarms($device, $alarmRepository);
        }
    }

    private function processDeviceAlarms(Device $device, DeviceAlarmRepository $alarmRepository): void
    {
        $entries = [null, 1, 2];

        foreach ($entries as $entry) {
            $alarms = $alarmRepository->findAlarmsThatNeedsNotification($device, $entry);

            if (empty($alarms)) {
                continue;
            }

            $this->notifyByMail($alarms, $entry);
            $this->notifyBySMS($alarms);
            $this->markAlarmsAsNotified($alarms);
        }
    }

    /**
     * @param array<DeviceAlarm> $alarms
     */
    private function markAlarmsAsNotified(array $alarms): void
    {
        foreach ($alarms as $alarm) {
            $alarm->setIsNotified(true);
        }
        $this->entityManager->flush();
    }

    /**
     * @param array<DeviceAlarm> $alarms
     */
    public function notifyBySMS(array $alarms): void
    {
        foreach ($alarms as $alarm) {
            $this->sendSmsForAlarm($alarm);
        }
    }

    private function sendSmsForAlarm(DeviceAlarm $alarm): void
    {
        $recipients = $this->alarmRecipients->getRecipientsForSms($alarm);

        if (empty($recipients)) {
            return;
        }

        $message = $this->buildSmsMessage($alarm);
        $this->infobipClient->sendMessage($recipients, $message);
        $this->logSmsNotifications($alarm, $recipients, $message);
    }

    private function buildSmsMessage(DeviceAlarm $alarm): string
    {
        $message = sprintf('%s %s', $alarm->getShortMessage(), self::SMS_SIGNATURE);
        $converted = iconv('UTF-8', 'ASCII//TRANSLIT', $message);

        return $converted !== false ? $converted : $message;
    }

    private function logSmsNotifications(DeviceAlarm $alarm, array $recipients, string $message): void
    {
        foreach ($recipients as $recipient) {
            $log = $this->alarmLogFactory->create($alarm, $recipient, DeviceAlarmLog::TYPE_PHONE_SMS, $message);
            $this->entityManager->persist($log);
        }
        $this->entityManager->flush();
    }

    /**
     * @param array<DeviceAlarm> $alarms
     */
    private function notifyByMail(array $alarms, ?int $entry = null): void
    {
        if (empty($alarms)) {
            return;
        }

        foreach ($alarms as $alarm) {
            $this->sendEmailForAlarm($alarm, $alarms, $entry);
        }
    }

    private function sendEmailForAlarm(DeviceAlarm $alarm, array $alarms, ?int $entry): void
    {
        $device = $alarm->getDevice();
        $recipients = $this->collectEmailRecipients($device, $alarm->getType(), $entry);

        if (empty($recipients)) {
            return;
        }

        $email = $this->buildEmail($device, $alarms, $recipients);
        $this->mailer->send($email);
        $this->logEmailNotification($device, $alarms, $recipients, $entry);
    }

    private function collectEmailRecipients(Device $device, string $alarmType, ?int $entry): array
    {
        $emails = self::INTERNAL_RECIPIENTS;

        $emails = $this->addClientEmails($device, $emails);
        $emails = $this->addGeneralApplicationEmails($device, $alarmType, $emails);

        if ($entry !== null) {
            $emails = $this->addEntryApplicationEmails($device, $alarmType, $entry, $emails);
        }

        return $this->filterUniqueEmails($emails);
    }

    private function addClientEmails(Device $device, array $emails): array
    {
        $settings = $device->getClient()->getClientSetting();
        $clientEmails = $settings->getAlarmNotificationList() ?? [];

        foreach ((array) $clientEmails as $clientEmail) {
            if (is_string($clientEmail) && $clientEmail !== '') {
                $emails[] = $clientEmail;
            }
        }

        return $emails;
    }

    private function addGeneralApplicationEmails(Device $device, string $alarmType, array $emails): array
    {
        $isDeviceLevelAlarm = AlarmTypeGroups::isDeviceLevelAlarm($alarmType);
        $generalEmails = $device->getApplicationEmailList() ?? [];

        foreach ($generalEmails as $key => $value) {
            if (is_string($value) && $isDeviceLevelAlarm) {
                $emails[] = $value;
                continue;
            }

            if (is_string($key) && is_array($value)) {
                $isActive = (bool) ($value['is_device_power_supply_active'] ?? true);
                if ($isDeviceLevelAlarm && $isActive) {
                    $emails[] = $key;
                }
            }
        }

        return $emails;
    }

    private function addEntryApplicationEmails(Device $device, string $alarmType, int $entry, array $emails): array
    {
        $entryData = $device->getEntryData($entry) ?? [];
        $entryAppEmails = $entryData['application_email'] ?? [];

        $isTemperatureAlarm = AlarmTypeGroups::isTemperatureAlarm($alarmType);
        $isHumidityAlarm = AlarmTypeGroups::isHumidityAlarm($alarmType);

        foreach ($entryAppEmails as $key => $value) {
            if (is_string($value) && ($isTemperatureAlarm || $isHumidityAlarm)) {
                $emails[] = $value;
                continue;
            }

            if (is_string($key) && is_array($value)) {
                if ($isTemperatureAlarm && (bool) ($value['is_temperature_active'] ?? true)) {
                    $emails[] = $key;
                    continue;
                }
                if ($isHumidityAlarm && (bool) ($value['is_humidity_active'] ?? true)) {
                    $emails[] = $key;
                }
            }
        }

        return $emails;
    }

    private function filterUniqueEmails(array $emails): array
    {
        $filtered = array_filter($emails, static fn($e) => is_string($e) && $e !== '');

        return array_values(array_unique($filtered));
    }

    private function buildEmail(Device $device, array $alarms, array $recipients): Email
    {
        $subject = sprintf('Aktivni alarmi za uređaj: %s', $device->getName());

        return (new Email())
            ->from(self::SENDER_EMAIL)
            ->sender(self::SENDER_EMAIL)
            ->to(...$recipients)
            ->bcc(self::BCC_LOG_EMAIL)
            ->subject($subject)
            ->html($this->twig->render('v2/mail/active_alarm_notification.html.twig', [
                'device' => $device,
                'alarms' => $alarms,
            ]));
    }

    private function logEmailNotification(Device $device, array $alarms, array $recipients, ?int $entry): void
    {
        try {
            $alarmIds = array_map(static fn(DeviceAlarm $a) => $a->getId(), $alarms);
            $alarmTypes = array_map(static fn(DeviceAlarm $a) => $a->getType(), $alarms);

            $this->logger->info('AlarmNotifier: sent alarm notification email.', [
                'device_id' => $device->getId(),
                'device_name' => $device->getName(),
                'entry' => $entry,
                'recipients' => $recipients,
                'subject' => sprintf('Aktivni alarmi za uređaj: %s', $device->getName()),
                'alarm_ids' => $alarmIds,
                'alarm_types' => $alarmTypes,
                'alarm_count' => count($alarms),
            ]);
        } catch (\Throwable $e) {
            // Fallback to error_log if main logger fails
            error_log(sprintf('AlarmNotifier logging failed: %s', $e->getMessage()));
        }
    }
}
