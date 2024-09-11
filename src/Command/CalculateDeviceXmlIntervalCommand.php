<?php

namespace App\Command;

use App\Entity\Device;
use App\Repository\DeviceDataRepository;
use App\Repository\DeviceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:calculate-xml-interval',
    description: 'Function for calculating xml interval for getting xml, once a day',
)]
class CalculateDeviceXmlIntervalCommand extends Command
{
    public function __construct(
        private DeviceRepository $deviceRepository,
        private DeviceDataRepository $deviceDataRepository,
        private EntityManagerInterface $entityManager
    )
    {
        parent::__construct();
    }
    protected function configure(): void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $devices = $this->deviceRepository->findBy(['isDeleted' => false]);

        foreach ($devices as $device) {
            $interval = $this->getIntervalForDevice($device);

            if ($interval > 0) {
                $device->setXmlInterval($interval);
            }
        }

        $this->entityManager->flush();

        return Command::SUCCESS;
    }

    private function getIntervalForDevice(Device $device)
    {
        $deviceData = $this->deviceDataRepository->getLast100Records($device->getId());
        $times = [];

        foreach ($deviceData as $key => $data) {
            if ($key === 0) {
                continue;
            }

            $previousTime = $deviceData[$key - 1]->getDeviceDate();
            $currentDate = $data->getDeviceDate();
            $diff = $previousTime->diff($currentDate);

            $timeDifference = $diff->i + $diff->h * 60;

            if (!array_key_exists($timeDifference, $times)) {
                $times[$timeDifference] = 0;
            }

            $times[$timeDifference]++;
        }

        if (!$times) {
            return 0;
        }

        return array_search(max($times), $times, true);
    }
}
