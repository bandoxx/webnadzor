<?php

namespace App\Service\Notify;

use App\Entity\Device;
use App\Entity\DeviceAlarm;
use App\Entity\DeviceAlarmLog;
use App\Service\Alarm\AlarmLog\AlarmLogFactory;
use App\Service\Alarm\AlarmRecipients;
use App\Service\Alarm\Types\DeviceSupplyOff;
use App\Service\Alarm\Types\HumidityHigh;
use App\Service\Alarm\Types\HumidityLow;
use App\Service\Alarm\Types\Standalone\DeviceOffline;
use App\Service\Alarm\Types\TemperatureHigh;
use App\Service\Alarm\Types\TemperatureLow;
use App\Service\APIClient\InfobipClient;
use App\Service\Mailer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Psr\Log\LoggerInterface;
use Monolog\Attribute\WithMonologChannel;

class AlarmNotifier
{

    public function __construct(
        private readonly Mailer $mailer,
        private readonly EntityManagerInterface $entityManager,
        private readonly InfobipClient $infobipClient,
        private readonly AlarmRecipients $alarmRecipients,
        private readonly Environment $twig,
        private readonly AlarmLogFactory $alarmLogFactory,
        #[WithMonologChannel('mailer')]
        private readonly LoggerInterface $logger,
    )
    {}

    public function notify(): void
    {
        $devices = $this->entityManager->getRepository(Device::class)->findAll();
        $deviceAlarmRepository = $this->entityManager->getRepository(DeviceAlarm::class);

        foreach ($devices as $device) {
            // Process main alarms (no sensor)
            $alarms = $deviceAlarmRepository->findAlarmsThatNeedsNotification($device);
            $this->notifyByMail($alarms);
            $this->notifyBySMS($alarms);

            foreach ($alarms as $alarm) {
                $alarm->setIsNotified(true);
            }
            $this->entityManager->flush();

            // Process alarms for entries 1 and 2
            for ($entry = 1; $entry <= 2; $entry++) {
                $entryAlarms = $deviceAlarmRepository->findAlarmsThatNeedsNotification($device, $entry);
                $this->notifyByMail($entryAlarms, $entry);
                $this->notifyBySMS($entryAlarms);

                foreach ($entryAlarms as $alarm) {
                    $alarm->setIsNotified(true);
                }
                $this->entityManager->flush();
            }
        }
    }

    /**
     * @param array<DeviceAlarm> $alarms
     */
    public function notifyBySMS(array $alarms): void
    {
        foreach ($alarms as $alarm) {
            $recipients = $this->alarmRecipients->getRecipientsForSms($alarm);

            if (empty($recipients)) {
                continue;
            }

            $message = sprintf("%s Intelteh D.O.O", $alarm->getShortMessage());
            $this->infobipClient->sendMessage($recipients, iconv('UTF-8', 'ASCII//TRANSLIT', $message));

            foreach ($recipients as $recipient) {
                $log = $this->alarmLogFactory->create($alarm, $recipient, DeviceAlarmLog::TYPE_PHONE_SMS, $message);
                $this->entityManager->persist($log);
            }

            $this->entityManager->flush();
        }
    }

    /**
     * @param Device $device
     * @param array<DeviceAlarm> $alarms
     */
    public function notifyByVoiceMessage(array $alarms)
    {
        // TODO:
        //foreach ($alarms as $alarm) {
        //    $recipients = $this->alarmRecipients->getRecipientsForVoiceMessage($alarm);
        //
        //    $this->infobipClient->sendVoiceMessage($recipients);
        //}
    }

    /**
     * @param array<DeviceAlarm> $alarms
     * @param int|null $entry
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    private function notifyByMail(array $alarms, ?int $entry = null): void
    {
        if (empty($alarms)) {
            return;
        }

        foreach ($alarms as $alarm) {
            /** @var Device $device */
            $device = $alarm->getDevice();

            $settings = $device->getClient()->getClientSetting();

            $alarmType = $alarm->getType();

            // Start with internal recipients
            $emails = ['damir.cerjak@intelteh.hr', 'petar.simic@intelteh.hr'];

            // Client-level notification list (already a flat list of emails)
            $clientEmails = $settings->getAlarmNotificationList() ?? [];
            foreach ((array) $clientEmails as $ce) {
                if (is_string($ce) && $ce !== '') {
                    $emails[] = $ce;
                }
            }

            // Alarm type flags
            $powerSupplyAlarm = in_array($alarmType, [DeviceSupplyOff::TYPE, DeviceOffline::TYPE], true);
            $isTempAlarm = in_array($alarmType, [TemperatureHigh::TYPE, TemperatureLow::TYPE], true);
            $isHumAlarm = in_array($alarmType, [HumidityHigh::TYPE, HumidityLow::TYPE], true);

            // General application emails can be either a flat list ["a@b.com", ...]
            // or a map ["a@b.com" => ["is_device_power_supply_active" => true], ...]
            $general = $device->getApplicationEmailList() ?? [];
            foreach ($general as $key => $value) {
                if (is_string($value)) {
                    // Flat list element
                    if ($powerSupplyAlarm) {
                        $emails[] = $value;
                    }
                } elseif (is_string($key) && is_array($value)) {
                    // Map: email => settings
                    $isActive = (bool)($value['is_device_power_supply_active'] ?? true);
                    if ($powerSupplyAlarm && $isActive) {
                        $emails[] = $key;
                    }
                }
            }

            // Entry-specific application emails (either flat list or map)
            if ($entry) {
                $entryData = $device->getEntryData($entry) ?? [];
                $entryAppEmails = $entryData['application_email'] ?? [];
                foreach ($entryAppEmails as $key => $value) {
                    if (is_string($value)) {
                        // Flat list: default to receive temp/humidity alarms
                        if ($isTempAlarm || $isHumAlarm) {
                            $emails[] = $value;
                        }
                    } elseif (is_string($key) && is_array($value)) {
                        // Map: email => settings
                        if ($isTempAlarm && (bool)($value['is_temperature_active'] ?? true)) {
                            $emails[] = $key;
                            continue;
                        }
                        if ($isHumAlarm && (bool)($value['is_humidity_active'] ?? true)) {
                            $emails[] = $key;
                        }
                    }
                }
            }

            // Keep only unique, non-empty strings
            $emails = array_values(array_unique(array_filter($emails, static fn($e) => is_string($e) && $e !== '')));

            if (empty($emails)) {
                continue;
            }

            $email = (new Email())
                ->from('rht@intelteh.hr')
                ->sender('rht@intelteh.hr')
                ->to(...$emails)
                ->bcc('logs@banox.dev')
                ->subject(sprintf("Aktivni alarmi za uređaj: %s", $device->getName()))
                ->html($this->twig->render('v2/mail/active_alarm_notification.html.twig', [
                    'device' => $device,
                    'alarms' => $alarms
                ]));

            $this->mailer->send($email);

            // Log mail notification via Monolog (mailer channel)
            try {
                $alarmIds = array_map(static fn($a) => method_exists($a, 'getId') ? $a->getId() : null, $alarms);
                $alarmTypes = array_map(static fn($a) => method_exists($a, 'getType') ? $a->getType() : null, $alarms);
                $this->logger->info('AlarmNotifier: sent alarm notification email.', [
                    'device_id' => $device->getId(),
                    'device_name' => $device->getName(),
                    'entry' => $entry,
                    'recipients' => $emails,
                    'subject' => sprintf('Aktivni alarmi za uređaj: %s', $device->getName()),
                    'alarm_ids' => $alarmIds,
                    'alarm_types' => $alarmTypes,
                    'alarm_count' => count($alarms),
                ]);
            } catch (\Throwable $e) {
                // Do not break the flow if logging fails
            }
        }
    }
}