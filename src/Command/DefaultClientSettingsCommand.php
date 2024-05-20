<?php

namespace App\Command;

use App\Factory\ClientSettingFactory;
use App\Repository\ClientRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:default-client-settings',
    description: 'Initial assignment of setting entity in client',
)]
class DefaultClientSettingsCommand extends Command
{
    public function __construct(private ClientRepository $clientRepository, private EntityManagerInterface $entityManager, private ClientSettingFactory $clientSettingFactory)
    {
        parent::__construct();
    }

    protected function configure(): void
    {}

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $clients = $this->clientRepository->findAll();

        foreach ($clients as $client) {
            $clientSettings = $this->clientSettingFactory->create($client);

            $this->entityManager->persist($clientSettings);
            $this->entityManager->flush();
        }

        return Command::SUCCESS;
    }
}
