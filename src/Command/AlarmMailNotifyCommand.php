<?php

namespace App\Command;

use App\Repository\DeviceAlarmRepository;
use App\Repository\DeviceRepository;
use App\Service\Notify\AlarmNotifier;
use Doctrine\ORM\EntityManagerInterface;
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
        private AlarmNotifier $mailer,
        private DeviceRepository $deviceRepository,
        private DeviceAlarmRepository $deviceAlarmRepository,
        private EntityManagerInterface $entityManager
    )
    {
        parent::__construct();
    }
    protected function configure(): void
    {}

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln(sprintf("%s - %s started", (new \DateTime())->format('Y-m-d H:i:s'), $this->getName()));

        $devices = $this->deviceRepository->findAll();

        foreach ($devices as $device) {
            $alarms = $this->deviceAlarmRepository->findAlarmsThatNeedsNotification($device);

            if (!$alarms) {
                continue;
            }

            $this->mailer->notify($device, $alarms);

            foreach ($alarms as $alarm) {
                $alarm->setIsNotified(true);
            }

            $this->entityManager->flush();
        }

        $output->writeln(sprintf("%s - %s finished successfully", (new \DateTime())->format('Y-m-d H:i:s'), $this->getName()));

        return Command::SUCCESS;
    }
}
