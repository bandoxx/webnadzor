<?php

namespace App\Command;

use App\Factory\DeviceDataFactory;
use App\Factory\LockFactory;
use App\Repository\DeviceRepository;
use App\Service\Alarm\ValidatorCollection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
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
        private DeviceDataFactory      $deviceDataFactory,
        private DeviceRepository       $deviceRepository,
        private EntityManagerInterface $entityManager,
        private LoggerInterface        $logger,
        private ValidatorCollection    $alarmValidator,
        private LockFactory            $lockFactory,
        private string                 $xmlDirectory
    )
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $lock = $this->lockFactory->create('parse-xml');

        if (!$lock->acquire()) {
            $output->writeln('Parser is running already...');
        }

        $output->writeln(sprintf("%s - %s started", (new \DateTime())->format('Y-m-d H:i:s'), $this->getName()));

        $xmls = array_diff(scandir($this->xmlDirectory), ['.', '..']);

        foreach ($xmls as $fileName) {
            if (str_contains($fileName, '-Settings')) {
                continue;
            }

            $name = rtrim($fileName, '.xml');
            $xmlPath = sprintf("%s/%s", $this->xmlDirectory, $fileName);
            $device = $this->deviceRepository->binaryFindOneByName($name);

            if (!$device) {
                $this->logger->error(sprintf("Client with file name %s doesn't exist!", $name));
                unlink($xmlPath);
                continue;
            }

            if ($device->isParserActive() === false) {
                $this->logger->error(sprintf("Client with file name %s is not currently active!", $name));
                unlink($xmlPath);

                continue;
            }

            if (empty(filesize($xmlPath)) === true) {
                continue;
            }

            $deviceData = $this->deviceDataFactory->createFromXml($device, $xmlPath);

            if (!$deviceData) {
                $this->logger->error(sprintf("XML Parser failed for %s", $xmlPath));
                continue;
            }

            $this->entityManager->persist($deviceData);
            $this->entityManager->flush();

            unlink($xmlPath);

            $this->alarmValidator->validate($deviceData, $device->getClient()->getClientSetting());
        }

        $lock->release();

        $output->writeln(sprintf("%s - %s finished successfully", (new \DateTime())->format('Y-m-d H:i:s'), $this->getName()));

        return Command::SUCCESS;
    }
}
