<?php

namespace App\Command;

use App\Repository\DeviceDataRepository;
use App\Repository\DeviceRepository;
use App\Service\Notify\MissingXmlModel;
use App\Service\Notify\MissingXmlNotify;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:missing-xml:notify',
    description: 'Notify users about alarms.',
)]
class MissingXmlNotifyCommand extends Command
{
    public const MINUTES_IN_DAY = 24 * 60;
    public function __construct(
        private readonly MissingXmlNotify $notify,
        private readonly DeviceRepository $deviceRepository,
        private readonly DeviceDataRepository $deviceDataRepository
    )
    {
        parent::__construct();
    }
    protected function configure(): void
    {}

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $notifications = [];
        $devices = $this->deviceRepository->findBy(['isDeleted' => false]);

        foreach ($devices as $device) {
            if ($device->getXmlInterval() === 0 || $device->isParserActive() === false) {
                continue;
            }

            $numberOfLogs = $this->deviceDataRepository->getNumberOfRecordsForLastDay($device->getId());
            $expectedNumberOfLogs = self::MINUTES_IN_DAY / $device->getXmlInterval();

            if (self::MINUTES_IN_DAY / $device->getXmlInterval() !== $numberOfLogs) {
                $notifications[] = (new MissingXmlModel())
                    ->setDevice($device)
                    ->setNumberOfLogs($numberOfLogs)
                    ->setExpectedNumberOfLogs($expectedNumberOfLogs)
                ;
            }
        }

        if (!empty($notifications)) {
            $this->notify->notify($notifications);
        }

        return Command::SUCCESS;
    }
}
