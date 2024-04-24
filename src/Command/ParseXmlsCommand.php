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
        $xmls = array_diff(scandir($this->xmlDirectory), ['.', '..']);

        foreach ($xmls as $fileName) {
            $xmlPath = sprintf("%s/%s", $this->xmlDirectory, $fileName);

            $device = $this->deviceRepository->findOneBy(['xmlName' => $fileName]);

            if (!$device) {
                // xml doesn't exist in our database
            }

            if ($device->isParserActive() === false) {
                continue;
            }

            $deviceData = $this->deviceDataFactory->createFromXml($device, $xmlPath);

            $this->entityManager->persist($deviceData);
            $this->entityManager->flush();
        }


        return Command::SUCCESS;
    }
}
