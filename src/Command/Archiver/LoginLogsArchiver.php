<?php

namespace App\Command\Archiver;

use App\Entity\Client;
use App\Factory\LoginLogArchiveFactory;
use App\Repository\ClientRepository;
use App\Repository\LoginLogRepository;
use App\Service\Archiver\LoginLog\LoginLogPDFArchiver;
use App\Service\Exception\ExceptionFormatter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

#[AsCommand(
    name: 'app:login-log-archiver',
    description: 'Command for making PDF login archive',
)]
class LoginLogsArchiver extends Command
{
    public function __construct(
        private LoginLogPDFArchiver    $loginLogArchive,
        private LoginLogRepository     $loginLogRepository,
        private ClientRepository       $clientRepository,
        private EntityManagerInterface $entityManager,
        private LoginLogArchiveFactory $loginLogArchiveFactory,
        private SluggerInterface       $slugger
    )
    {
        parent::__construct();
    }
    protected function configure(): void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln(sprintf("%s - %s started", (new \DateTime())->format('Y-m-d H:i:s'), $this->getName()));

        $date = (new \DateTime('-1 day'))->setTime(0, 0, 0);

        $clients = $this->clientRepository->findAllActive();

        foreach ($clients as $client) {
            $data = $this->loginLogRepository->findByClientAndForDay($client, $date);

            $this->generateDailyReport($client, $data, $date);
        }

        $output->writeln(sprintf("%s - %s finished successfully", (new \DateTime())->format('Y-m-d H:i:s'), $this->getName()));

        return Command::SUCCESS;
    }

    private function generateDailyReport($client, $data, $date): void
    {
        $fileName = $this->generateFilename($client, $date);
        $this->loginLogArchive->saveDaily($client, $data, $date, $fileName);

        $archive = $this->loginLogArchiveFactory->create($client, $date, $fileName);

        $this->entityManager->persist($archive);
        $this->entityManager->flush();
    }

    private function generateFilename(Client $client, $date): string
    {
        return sprintf('%s_%s', $this->slugger->slug($client->getName()), $date->format('d-m-Y'));
    }
}
