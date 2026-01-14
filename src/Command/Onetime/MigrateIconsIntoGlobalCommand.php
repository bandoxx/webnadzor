<?php

namespace App\Command\Onetime;

use App\Entity\Device;
use App\Repository\DeviceIconRepository;
use App\Repository\DeviceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:migrate-icons-into-global',
    description: 'Add a short description for your command',
)]
class MigrateIconsIntoGlobalCommand extends Command
{
    public function __construct(
        private DeviceRepository $deviceRepository,
        private DeviceIconRepository $deviceIconRepository,
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
        $icons = $this->deviceIconRepository->findAll();
        $oldIcons = [];

        foreach ($icons as $icon) {
            $hash = md5(sprintf("%s%s", $icon->getFilename(), $icon->getTitle()));

            if (!array_key_exists($hash, $oldIcons)) {
                $oldIcons[$hash] = [
                    'id' => $icon->getId(),
                    'name' => $icon->getFilename(),
                    'title' => $icon->getTitle(),
                ];
            }

            $oldIcons[$hash]['ids'][] = $icon->getId();
        }

        $replaces = [];

        foreach ($oldIcons as $key => $oldIcon) {
            if (count($oldIcon['ids']) === 1) {
                unset($oldIcons[$key]);
            } else {
                $replaces[$oldIcon['id']] = $oldIcon['ids'];
            }
        }

        if (!$replaces) {
            return Command::SUCCESS;
        }

        $devices = $this->deviceRepository->findAll();

        foreach ($devices as $device) {
            foreach (Device::SENSOR_ENTRIES as $entry) {
                $data = $device->getEntryData($entry);

                foreach ($replaces as $key => $replace) {
                    if (in_array($data['t_image'], $replace)) {
                        $output->writeln(sprintf("Replaced %s.%s.t_image to %s", $device->getId(), $entry, $key));
                        $device->setEntryData($entry, 't_image', $key);
                    }

                    if (in_array($data['rh_image'], $replace)) {
                        $output->writeln(sprintf("Replaced %s.%s.rh_image to %s", $device->getId(), $entry, $key));
                        $device->setEntryData($entry, 'rh_image', $key);
                    }
                }
            }
        }

        // Single flush after all changes
        $this->entityManager->flush();

        return Command::SUCCESS;
    }

    private function switchId($oldIcons)
    {

    }
}
