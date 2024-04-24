<?php

namespace App\Command;

use App\Factory\DeviceDataFactory;
use App\Repository\DeviceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:parse-xmls',
    description: 'Parse XMLs and insert into database',
)]
class ParseXmlsCommand extends Command
{
    protected function configure(): void
    {
    }

    public function __construct(
        private DeviceDataFactory $deviceDataFactory,
        private DeviceRepository $deviceRepository,
        private EntityManagerInterface $entityManager,
        private string $xmlDirectory
    )
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln(sprintf("%s - %s started", (new \DateTime())->format('Y-m-d H:i:s'), $this->getName()));

        $xmls = array_diff(scandir($this->xmlDirectory), ['.', '..']);

        foreach ($xmls as $fileName) {
            $name = rtrim($fileName, '.xml');
            $xmlPath = sprintf("%s/%s", $this->xmlDirectory, $fileName);
            $device = $this->deviceRepository->findOneBy(['xmlName' => $name]);

            if (!$device) {
                $output->writeln(sprintf("Client with file name %s doesn't exist!", $name));
                continue;
            }

            if ($device->isParserActive() === false) {
                $output->writeln(sprintf("Client with file name %s is not currently active!", $name));
                continue;
            }

            $deviceData = $this->deviceDataFactory->createFromXml($device, $xmlPath);

            $this->entityManager->persist($deviceData);
            $this->entityManager->flush();
        }

        $output->writeln(sprintf("%s - %s finished successfully", (new \DateTime())->format('Y-m-d H:i:s'), $this->getName()));

        return Command::SUCCESS;
    }
}
