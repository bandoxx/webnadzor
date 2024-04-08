<?php

namespace App\Command;

use App\Command\DatabaseMigration\DeviceDataImport;
use App\Command\DatabaseMigration\UserDataImport;
use App\Entity\Client;
use App\Entity\ClientInfo;
use App\Entity\Device;
use App\Entity\DeviceData;
use App\Factory\DeviceDataEntryFactory;
use App\Factory\DeviceDataFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:migrate-database',
    description: 'Add a short description for your command',
)]
class MigrateDatabases extends Command
{
    protected function configure(): void
    {
    }

    public function __construct(
        private EntityManagerInterface $entityManager,
        private DeviceDataImport $deviceDataImport,
        private UserDataImport $userDataImport
    )
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        //$databases = ['nadzor-old', 'cedevita-old'];
        $databases = ['nadzor-old'];

        foreach ($databases as $databaseName) {
            $pdo = new \PDO("mysql:host=nadzor_mysql;dbname=$databaseName", 'root', 'root');
            $pdo->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
            $pdo->setAttribute(\PDO::ATTR_PERSISTENT, false);

            $client = $this->migrateClient($pdo, $databaseName);
            $clientId = $client->getId();

            $this->deviceDataImport->import($pdo, $client);

            $client = $this->entityManager->getRepository(Client::class)->find($clientId);

            $this->userDataImport->import($pdo, $client);
        }

        return Command::SUCCESS;
    }

    private function migrateClient(\PDO $pdo, $databaseName): Client
    {
        $client = $this->getOrCreateClient($databaseName);

        $info = $pdo->query('SELECT * FROM `config_info`')->fetch(\PDO::FETCH_OBJ);

        if (!$info) {
            return $client;
        }

        $clientInfo = new ClientInfo();
        $clientInfo->setClient($client)
            ->setHost($info->host)
            ->setUsername($info->username)
            ->setPassword($info->password)
        ;

        $this->entityManager->persist($clientInfo);
        $this->entityManager->flush();

        return $client;
    }

    private function getOrCreateClient($databaseName)
    {
        $client = $this->entityManager->getRepository(Client::class)->findOneBy(['name' => $databaseName]);

        if ($client) {
            return $client;
        }

        $client = new Client();
        $client->setName($databaseName);

        $this->entityManager->persist($client);
        $this->entityManager->flush();

        return $client;
    }
}
