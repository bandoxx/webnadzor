<?php

namespace App\Command;

use App\Factory\DeviceDataFactory;
use App\Factory\LockFactory;
use App\Factory\UnresolvedDeviceDataFactory;
use App\Repository\DeviceRepository;
use App\Service\Alarm\ValidatorCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
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
        private readonly ManagerRegistry $managerRegistry,
        private LoggerInterface        $logger,
        private ValidatorCollection    $alarmValidator,
        private LockFactory            $lockFactory,
        private UnresolvedDeviceDataFactory   $unresolvedXMLFactory,
        private string                 $xmlDirectory
    )
    {
        parent::__construct();
    }

    private function getEntityManager(): EntityManagerInterface
    {
        $em = $this->managerRegistry->getManager();
        if (!$em->isOpen()) {
            $this->logger->warning('EntityManager was closed, resetting...');
            $this->managerRegistry->resetManager();
            $em = $this->managerRegistry->getManager();
        }
        return $em;
    }

    private function getDeviceRepository(): DeviceRepository
    {
        return $this->getEntityManager()->getRepository(Device::class);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln(sprintf("%s - %s started", (new \DateTime())->format('Y-m-d H:i:s'), $this->getName()));

        $lock = $this->lockFactory->create('parse-xml');

        if (!$lock->acquire()) {
            $output->writeln(sprintf("%s - %s closed - parser already running", (new \DateTime())->format('Y-m-d H:i:s'), $this->getName()));
            return Command::FAILURE;
        }

        $xmls = array_diff(scandir($this->xmlDirectory), ['.', '..']);

        foreach ($xmls as $fileName) {
            if (str_contains($fileName, '-Settings')) {
                continue;
            }

            $name = rtrim($fileName, '.xml');
            $xmlPath = sprintf("%s/%s", $this->xmlDirectory, $fileName);
            $device = $this->getDeviceRepository()->binaryFindOneByName($name);

            if (!$device) {
                try {
                    $this->saveUnresolvedXml($xmlPath);
                } catch (\Throwable $e) {
                    $this->logger->error(sprintf("Client with file name %s doesn't exist!", $name));
                    $output->writeln($e->getMessage());
                } finally {
                    unlink($xmlPath);
                }

                continue;
            }

            if ($device->isParserActive() === false) {
                try {
                    $this->saveUnresolvedXml($xmlPath);
                } catch (\Throwable $e) {
                    $this->logger->error(sprintf("Client with file name %s is not currently active!", $name));
                    $output->writeln($e->getMessage());
                } finally {
                    unlink($xmlPath);
                }

                continue;
            }

            if (empty(filesize($xmlPath)) === true) {
                continue;
            }

            try {
                $deviceData = $this->deviceDataFactory->createFromXml($device, $xmlPath);

                $em = $this->getEntityManager();
                $em->beginTransaction();
                try {
                    $em->persist($deviceData);
                    $em->flush();
                    $em->commit();
                } catch (\Throwable $e) {
                    if ($em->getConnection()->isTransactionActive()) {
                        $em->rollback();
                    }
                    $em->clear();
                    throw $e;
                }

                unlink($xmlPath);
                $this->alarmValidator->validate($deviceData, $device->getClient()->getClientSetting());
            } catch (\Throwable $e) {
                $this->logger->error($e->getMessage());
                continue;
            }
        }

        $lock->release();

        $output->writeln(sprintf("%s - %s finished successfully", (new \DateTime())->format('Y-m-d H:i:s'), $this->getName()));

        return Command::SUCCESS;
    }

    private function saveUnresolvedXml(string $xmlPath): void
    {
        $unresolvedXML = $this->unresolvedXMLFactory->createFromXml($xmlPath);

        $em = $this->getEntityManager();
        $em->beginTransaction();
        try {
            $em->persist($unresolvedXML);
            $em->flush();
            $em->commit();
        } catch (\Throwable $e) {
            if ($em->getConnection()->isTransactionActive()) {
                $em->rollback();
            }
            $em->clear();
            throw $e;
        }
    }
}
