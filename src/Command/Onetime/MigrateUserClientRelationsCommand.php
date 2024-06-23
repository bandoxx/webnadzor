<?php

namespace App\Command\Onetime;

use App\Repository\ClientRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:migrate:user-client-relations',
    description: 'Add a short description for your command',
)]
class MigrateUserClientRelationsCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository         $userRepository,
        private readonly ClientRepository       $clientRepository
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $clients = $this->clientRepository->findAll();
        $users = $this->userRepository->findAll();

        foreach ($users as $user) {
            $client = $user->getClient();

            if ($client) {
                $user->addClient($client);
            } else {
                foreach ($clients as $client) {
                    $user->addClient($client);
                }
            }

            $this->entityManager->flush();
        }

        return Command::SUCCESS;
    }
}
