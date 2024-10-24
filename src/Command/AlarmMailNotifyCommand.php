<?php

namespace App\Command;

use App\Service\Notify\AlarmNotifier;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:alarm:notify',
    description: 'Notify users about alarms.',
)]
class AlarmMailNotifyCommand extends Command
{
    public function __construct(
        private AlarmNotifier $alarmNotifier
    )
    {
        parent::__construct();
    }
    protected function configure(): void
    {}

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln(sprintf("%s - %s started", (new \DateTime())->format('Y-m-d H:i:s'), $this->getName()));

        $this->alarmNotifier->notify();

        $output->writeln(sprintf("%s - %s finished successfully", (new \DateTime())->format('Y-m-d H:i:s'), $this->getName()));

        return Command::SUCCESS;
    }
}
