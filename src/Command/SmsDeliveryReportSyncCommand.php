<?php

namespace App\Command;

use App\Entity\SmsDeliveryReport;
use App\Service\APIClient\InfobipClient;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:sms-delivery-report-sync',
    description: 'Add a short description for your command',
)]
class SmsDeliveryReportSyncCommand extends Command
{
    public function __construct(private InfobipClient $client, private EntityManagerInterface $entityManager)
    {
        parent::__construct();
    }

    protected function configure(): void
    {}

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $reports = $this->client->getSMSReports();
        $repository = $this->entityManager->getRepository(SmsDeliveryReport::class);

        foreach ($reports as $report) {
            $existingReport = $repository->findOneBy(['messageId' => $report->getMessageId()]);

            if ($existingReport) {
                $existingReport->setStatusName($report->getStatusName())
                    ->setStatusDescription($report->getStatusDescription())
                    ->setErrorName($report->getErrorName())
                    ->setErrorDescription($report->getErrorDescription())
                    ->setSentAt($report->getSentAt())
                ;
            } else {
                $this->entityManager->persist($report);
            }

            $this->entityManager->flush();
        }

        return Command::SUCCESS;
    }
}
