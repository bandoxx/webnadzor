<?php

namespace App\Service;

use App\Entity\Device;
use App\Entity\DeviceAlarm;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

class AlarmNotifier
{

    public function __construct(private MailerInterface $mailer, private Environment $twig)
    {}

    /**
     * @param Device $device
     * @param DeviceAlarm[] $alarms
     */
    public function notify(Device $device, array $alarms): void
    {
        $settings = $device->getClient()->getClientSetting();

        $emails = ['damir.cerjak@intelteh.hr', 'petar.simic@intelteh.hr'];
        $emails = array_merge($settings->getAlarmNotificationList(), $emails);

        $email = (new Email())
            ->from('info@intelteh.hr')
            ->sender('info@intelteh.hr')
            ->to(...$emails)
            ->cc('radivoje.pupovac98@gmail.com')
            ->subject(sprintf("Aktivni alarmi za uređaj: %s", $device->getName()))
            ->html($this->twig->render('mail/active_alarm_notification.html.twig', [
                'device' => $device,
                'alarms' => $alarms
            ]))
        ;

        $this->mailer->send($email);
    }

}