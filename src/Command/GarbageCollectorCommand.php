<?php

namespace App\Command;

use App\Service\DatabaseGarbageCollector;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:garbage-collector',
    description: 'Database garbage collector',
)]
class GarbageCollectorCommand extends Command
{
    public function __construct(
        private DatabaseGarbageCollector $garbageCollector
    )
    {
        parent::__construct();
    }
    protected function configure(): void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->garbageCollector->cleanLoginList();

        return Command::SUCCESS;
    }
}
