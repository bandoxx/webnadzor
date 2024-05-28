<?php

namespace App\Service\Notify;

use App\Entity\Device;
use App\Entity\DeviceAlarm;
use App\Service\Mailer;
use Symfony\Component\Mime\Email;
use Twig\Environment;

class AlarmNotifier
{

    public function __construct(private readonly Mailer $mailer, private readonly Environment $twig)
    {}

    /**
     * @param Device $device
     * @param DeviceAlarm[] $alarms
     */
    public function notify(Device $device, array $alarms): void
    {
        $settings = $device->getClient()->getClientSetting();

        $emails = ['damir.cerjak@intelteh.hr', 'petar.simic@intelteh.hr'];
        $emails = array_merge($device->getApplicationEmailList(), $settings->getAlarmNotificationList(), $emails);
        $emails = array_unique($emails);

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