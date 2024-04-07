<?php

namespace App\Command;

use App\Factory\DeviceDataFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:parse-xmls',
    description: 'Add a short description for your command',
)]
class ParseXmlsCommand extends Command
{
    protected function configure(): void
    {
    }

    public function __construct(
        private DeviceDataFactory $deviceDataFactory,
        private EntityManagerInterface $entityManager
    )
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $xmlDirectoryPath = sprintf("%s/../../data", __DIR__);
        $xmls = array_diff(scandir($xmlDirectoryPath), ['.', '..']);

        foreach ($xmls as $fileName) {

            for ($i = 0; $i < 100; $i++) {
                $xmlPath = sprintf("%s/%s", $xmlDirectoryPath, $fileName);
                $deviceData = $this->deviceDataFactory->createFromXml($xmlPath);

                $this->entityManager->persist($deviceData);
                $this->entityManager->flush();
            }
        }


        return Command::SUCCESS;
    }
}
