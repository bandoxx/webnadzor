<?php

namespace App\Service;

use App\Repository\SmtpRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\RawMessage;

class Mailer
{
    public function __construct(
        private SmtpRepository $smtpRepository,
        private LoggerInterface $logger,
    ) {
    }

    public function send(RawMessage $message): void
    {
        $smtp = $this->smtpRepository->findOneBy([]);

        if (!$smtp) {
            $this->logger->warning('Mailer: SMTP configuration not found, skipping send.', ['channel' => 'mailer']);
            return;
        }

        $dsn = sprintf('smtp://%s:%s@%s:%s',
            urlencode($smtp->getUsername()),
            urlencode($smtp->getPassword()),
            urlencode($smtp->getHost()),
            $smtp->getPort()
        );

        $this->logger->debug('Mailer: Preparing to send email via DSN.', ['dsn' => $dsn]);

        $transport = Transport::fromDsn($dsn);
        $mailer = new \Symfony\Component\Mailer\Mailer($transport);

        $mailer->send($message);

        $this->logger->info('Mailer: Email sent successfully.');
    }
}