<?php

namespace App\Command\DatabaseMigration;

use App\Command\DatabaseMigration\Import\DeviceDataImport;
use App\Command\DatabaseMigration\Import\UserDataImport;
use App\Entity\Client;
use App\Entity\ClientFtp;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AsCommand(
    name: 'app:database-migrate',
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
        private UserDataImport $userDataImport,
        private ParameterBagInterface $parameterBag,
    )
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln(sprintf("%s - %s started", (new \DateTime())->format('Y-m-d H:i:s'), $this->getName()));

        $databases = ['arnika_app', 'atlantictskoplje_app', 'cedevita_app', 'chevap_app', 'dcosijek_app', 'dcrijeka_app', 'dcsplit_app', 'drogakolinska_app', 'ekovent_app', 'ekovent_app2', 'farmacia_webapp', 'hipp_app', 'inteltehwnadzo_app', 'kbcsm_app', 'korcula_app', 'ldcvukovina_app', 'ljekarnacakovec_app', 'ljekarnerajic_app', 'ljekarnesibalic_app', 'medilabone_app', 'montana_app', 'primapharme_app', 'stark_app', 'vikdental_app'];
        $username = $this->parameterBag->get('database_username');
        $password = $this->parameterBag->get('database_password');

        foreach ($databases as $databaseName) {
            $pdo = new \PDO("mysql:host=localhost;dbname=$databaseName", $username, $password);
            $pdo->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
            $pdo->setAttribute(\PDO::ATTR_PERSISTENT, false);

            $client = $this->migrateClient($pdo, $databaseName);

            $output->writeln(sprintf("Migrating database - %s", $client->getName()));

            $clientId = $client->getId();

            $this->deviceDataImport->import($pdo, $client);

            $client = $this->entityManager->getRepository(Client::class)->find($clientId);

            $this->userDataImport->import($pdo, $client);
        }

        $output->writeln(sprintf("%s - %s finished successfully", (new \DateTime())->format('Y-m-d H:i:s'), $this->getName()));

        return Command::SUCCESS;
    }

    private function migrateClient(\PDO $pdo, $databaseName): Client
    {
        $client = $this->getOrCreateClient($databaseName);

        $info = $pdo->query('SELECT * FROM `config_info`')->fetch(\PDO::FETCH_OBJ);

        if (!$info) {
            return $client;
        }

        $clientFtp = new ClientFtp();
        $clientFtp->setClient($client)
            ->setHost($info->host)
            ->setUsername($info->username)
            ->setPassword($info->password)
        ;

        $this->entityManager->persist($clientFtp);
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
