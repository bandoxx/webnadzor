<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:database-download',
    description: 'Add a short description for your command',
)]
class DatabaseDownloadCommand extends Command
{
    public function __construct()
    {
        parent::__construct();
    }

    protected function configure(): void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $databases = [
            'arnika_app',
            'atlantictskoplje_app',
            'cedevita_app',
            'chevap_app',
            'dcosijek_app',
            'dcrijeka_app',
            'dcsplit_app',
            'drogakolinska_app',
            'ecodies_app',
            'ekovent_app',
            'ekovent_app2',
            'farmacia_webapp',
            'hipp_app',
            'inteltehwnadzo_app',
            'kbcsm_app',
            'korcula_app',
            'ldcvukovina_app',
            'ljekarnacakovec_app',
            'ljekarnerajic_app',
            'ljekarnesibalic_app',
            'medilabone_app',
            'montana_app',
            'primapharme_app',
            'stark_app',
            'vikdental_app',
        ];

        $session = ssh2_connect('nadzor');

        foreach ($databases as $database) {
            $filePath = sprintf('./db-backup/%s.sql', $database);
            ssh2_exec($session, sprintf('mysqldump %s > %s', $database, $filePath));
            ssh2_scp_recv($session, $filePath, sprintf('./%s.sql', $database));
        }

        ssh2_disconnect($session);

        return Command::SUCCESS;
    }
}