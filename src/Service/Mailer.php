<?php

namespace App\Service;

use App\Repository\SmtpRepository;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\RawMessage;

class Mailer
{
    public function __construct(private SmtpRepository $smtpRepository)
    {
    }

    public function send(RawMessage $message): void
    {
        $smtp = $this->smtpRepository->findOneBy([]);

        if (!$smtp) {
            return;
        }

        $dns = sprintf('smtp://%s:%s@%s:%s',
            urlencode($smtp->getUsername()),
            urlencode($smtp->getPassword()),
            urlencode($smtp->getHost()),
            $smtp->getPort()
        );

        $transport = Transport::fromDsn($dns);
        $mailer = new \Symfony\Component\Mailer\Mailer($transport);

        $mailer->send($message);
    }
}