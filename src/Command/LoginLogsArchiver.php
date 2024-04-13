<?php

namespace App\Command;

use App\Entity\Client;
use App\Entity\DeviceDataArchive;
use App\Factory\DeviceDataArchiveFactory;
use App\Factory\LoginLogArchiveFactory;
use App\Repository\ClientRepository;
use App\Repository\LoginLogRepository;
use App\Service\Archiver\PDF\LoginLogArchive;
use App\Service\Exception\ExceptionFormatter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

#[AsCommand(
    name: 'app:login-log-archiver',
    description: 'Command for making PDF login archive',
)]
class LoginLogsArchiver extends Command
{
    public function __construct(
        private LoginLogArchive $loginLogArchive,
        private LoginLogRepository $loginLogRepository,
        private ClientRepository $clientRepository,
        private EntityManagerInterface $entityManager,
        private LoginLogArchiveFactory $loginLogArchiveFactory,
        private SluggerInterface $slugger
    )
    {
        parent::__construct();
    }
    protected function configure(): void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $date = (new \DateTime('-1 day'))->setTime(0, 0, 0);

        $clients = $this->clientRepository->findAll();

        foreach ($clients as $client) {
            $data = $this->loginLogRepository->findByClientAndForDay($client, $date);

            try {
                $this->generateDailyReport($client, $data, $date);
            } catch (\Throwable $e) {
                $output->writeln(ExceptionFormatter::string($e));
                return Command::FAILURE;
            }
        }

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
