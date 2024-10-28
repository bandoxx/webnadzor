<?php

namespace App\Service\Notify;

use App\Entity\Device;
use App\Entity\DeviceAlarm;
use App\Entity\DeviceAlarmLog;
use App\Service\Alarm\AlarmLog\AlarmLogFactory;
use App\Service\Alarm\AlarmRecipients;
use App\Service\APIClient\InfobipClient;
use App\Service\Mailer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

class AlarmNotifier
{

    public function __construct(
        private readonly Mailer $mailer,
        private readonly EntityManagerInterface $entityManager,
        private readonly InfobipClient $infobipClient,
        private readonly AlarmRecipients $alarmRecipients,
        private readonly Environment $twig,
        private readonly AlarmLogFactory $alarmLogFactory
    )
    {}

    public function notify(): void
    {
        $devices = $this->entityManager->getRepository(Device::class)->findAll();

        foreach ($devices as $device) {
            $alarms = $this->entityManager->getRepository(DeviceAlarm::class)->findAlarmsThatNeedsNotification($device);

            if (!$alarms) {
                continue;
            }

            $this->notifyByMail($alarms);
            $this->notifyBySMS($alarms);

            foreach ($alarms as $alarm) {
                $alarm->setIsNotified(true);
            }

            $this->entityManager->flush();
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
                return;
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
     */
    private function notifyByMail(array $alarms): void
    {
        if (empty($alarms)) {
            return;
        }

        /** @var Device $device */
        $device = $alarms[0]->getDevice();

        $settings = $device->getClient()->getClientSetting();

        $emails = ['damir.cerjak@intelteh.hr', 'petar.simic@intelteh.hr'];
        $emails = array_merge($device->getApplicationEmailList(), $settings->getAlarmNotificationList(), $emails);
        $emails = array_unique($emails);

        $email = (new Email())
            ->from('info@intelteh.hr')
            ->sender('info@intelteh.hr')
            ->to(...$emails)
            ->bcc('radivoje.pupovac98@gmail.com')
            ->subject(sprintf("Aktivni alarmi za uređaj: %s", $device->getName()))
            ->html($this->twig->render('v2/mail/active_alarm_notification.html.twig', [
                'device' => $device,
                'alarms' => $alarms
            ]));

        $this->mailer->send($email);
    }
}