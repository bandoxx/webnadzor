<?php

namespace App\Service\Notify;

use App\Service\Mailer;
use Symfony\Component\Mime\Email;
use Twig\Environment;

class MissingXmlNotify
{

    public function __construct(private readonly Mailer $mailer, private readonly Environment $environment)
    {}

    /**
     * @param MissingXmlModel[] $notifications
     */
    public function notify(array $notifications): void
    {
        $emails = ['damir.cerjak@intelteh.hr', 'petar.simic@intelteh.hr'];

        $email = (new Email())
            ->from('info@intelteh.hr')
            ->sender('info@intelteh.hr')
            ->to(...$emails)
            ->cc('radivoje.pupovac98@gmail.com')
            ->subject(sprintf('Lista uređaja kojima fale logovi za dan: %s', (new \DateTime('-1 day'))->format('d.m.Y')))
            ->html($this->environment->render('mail/missing_xml_logs.html.twig', [
                'models' => $notifications
            ]))
        ;

        $this->mailer->send($email);
    }

}