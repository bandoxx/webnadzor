<?php

namespace App\Command\Archiver;

use App\Entity\Device;
use App\Entity\DeviceDataArchive;
use App\Factory\DeviceDataArchiveFactory;
use App\Repository\DeviceDataArchiveRepository;
use App\Repository\DeviceDataRepository;
use App\Repository\DeviceRepository;
use App\Service\Archiver\ArchiverInterface;
use App\Service\Archiver\DeviceData\DeviceDataPDFArchiver;
use App\Service\Archiver\DeviceData\DeviceDataXLSXArchiver;
use App\Service\Chart\ChartImageGenerator;
use App\Service\RawData\Factory\DeviceDataRawDataFactory;
use App\Service\RawData\RawDataHandler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:device-data-archiver',
    description: 'Command for making XLSX and PDF archives on daily and monthly level.',
)]
class DeviceDataArchiver extends Command
{
    public function __construct(
        private DeviceDataXLSXArchiver       $XLSXArchiver,
        private DeviceDataPDFArchiver        $PDFArchiver,
        private DeviceRepository             $deviceRepository,
        private DeviceDataRepository         $deviceDataRepository,
        private DeviceDataArchiveFactory     $deviceDataArchiveFactory,
        private DeviceDataArchiveRepository  $deviceDataArchiveRepository,
        private RawDataHandler               $rawDataHandler,
        private DeviceDataRawDataFactory     $deviceDataRawDataFactory,
        private EntityManagerInterface       $entityManager,
        private ChartImageGenerator          $chartImageGenerator
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('daily', null, InputOption::VALUE_NONE, 'Daily report')
            ->addOption('monthly', null, InputOption::VALUE_NONE, 'Monthly report')
            ->addOption('fromDate', null, InputOption::VALUE_OPTIONAL, 'From date', null)
            ->addOption('toDate', null, InputOption::VALUE_OPTIONAL, 'To date', null)
            ->addOption('deviceId', null, InputOption::VALUE_OPTIONAL, 'Device id', null)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln(sprintf("%s - %s started", (new \DateTime())->format('Y-m-d H:i:s'), $this->getName()));

        $daily = $input->getOption('daily');
        $monthly = $input->getOption('monthly');

        if (($daily || $monthly) === false) {
            $output->writeln("Daily or monthly report must be set!");

            return Command::FAILURE;
        }

        if ($deviceId = $input->getOption('deviceId')) {
            $devices[] = $this->deviceRepository->find($deviceId);
        } else {
            $devices = $this->deviceRepository->findDevicesWithIdentifiers();
        }

        $dates = $this->getDates($input->getOption('fromDate'), $input->getOption('toDate'));
        
        // Batch size for flushing to database
        $batchSize = 20;
        $archiveCount = 0;

        foreach ($dates as $date) {
            if ($daily) {
                // Pre-check which archives already exist to avoid unnecessary processing
                $existingDailyArchives = $this->preCheckExistingArchives($devices, $date, DeviceDataArchive::PERIOD_DAY);
                
                foreach ($devices as $device) {
                    $data = $this->deviceDataRepository->findByDeviceAndForDay($device, $date);
                    foreach([1, 2] as $entry) {
                        $fromDate = (clone $date)->setTime(0, 0, 0);
                        $toDate = (clone $date)->setTime(23, 59, 59);

                        // Check if archive already exists using the pre-fetched data
                        $archiveKey = $this->getArchiveKey($device->getId(), $entry, $date, DeviceDataArchive::PERIOD_DAY);
                        if (isset($existingDailyArchives[$archiveKey])) {
                            continue;
                        }

                        $this->chartImageGenerator->generateTemperatureAndHumidityChartImage($device, $entry, $fromDate, $toDate);
                        $this->generateDailyReport($device, $data, $entry, $date, false);
                        
                        $archiveCount++;
                        // Flush every $batchSize archives
                        if ($archiveCount % $batchSize === 0) {
                            $this->entityManager->flush();
                        }
                    }
                }
            }

            if ($monthly) {
                // Pre-check which archives already exist to avoid unnecessary processing
                $existingMonthlyArchives = $this->preCheckExistingArchives($devices, $date, DeviceDataArchive::PERIOD_MONTH);
                
                foreach ($devices as $device) {
                    $data = $this->deviceDataRepository->findByDeviceAndForMonth($device, $date);

                    foreach([1, 2] as $entry) {
                        $fromDate = (clone $date)->modify('first day of this month')->setTime(0, 0, 0);
                        $toDate = (clone $date)->modify('last day of this month')->setTime(23, 59, 59);

                        // Check if archive already exists using the pre-fetched data
                        $archiveKey = $this->getArchiveKey($device->getId(), $entry, $date, DeviceDataArchive::PERIOD_MONTH);
                        if (isset($existingMonthlyArchives[$archiveKey])) {
                            continue;
                        }

                        $this->chartImageGenerator->generateTemperatureAndHumidityChartImage($device, $entry, $fromDate, $toDate);
                        $this->generateMonthlyReport($device, $data, $entry, $date, false);
                        
                        $archiveCount++;
                        // Flush every $batchSize archives
                        if ($archiveCount % $batchSize === 0) {
                            $this->entityManager->flush();
                        }
                    }
                }
            }
        }
        
        // Final flush for any remaining archives
        if ($archiveCount % $batchSize !== 0) {
            $this->entityManager->flush();
        }

        $output->writeln(sprintf("%s - %s finished successfully", (new \DateTime())->format('Y-m-d H:i:s'), $this->getName()));
        return Command::SUCCESS;
    }

    private function generateDailyReport(Device $device, $data, $entry, $date, bool $flushImmediately = true): void
    {
        $fileName = $this->generateFilename($device->getDeviceIdentifier(), $entry, $date->format(ArchiverInterface::DAILY_FILENAME_FORMAT));

        $this->XLSXArchiver->saveDaily($device, $data, $entry, $date, $fileName);
        $archive = $this->PDFArchiver->saveDaily($device, $data, $entry, $date, $fileName);

        $this->rawDataHandler->encrypt($this->deviceDataRawDataFactory->create($data, $entry, $date), $archive->getFullPathWithoutExtension());

        $archive = $this->deviceDataArchiveFactory->create($device, $date, $entry, $fileName, DeviceDataArchive::PERIOD_DAY);

        $this->entityManager->persist($archive);
        
        // Only flush immediately if requested (for backward compatibility)
        if ($flushImmediately) {
            $this->entityManager->flush();
        }
    }

    private function generateMonthlyReport(Device $device, $data, $entry, $date, bool $flushImmediately = true): void
    {
        $fileName = $this->generateFilename($device->getDeviceIdentifier(), $entry, $date->format(ArchiverInterface::MONTHLY_FILENAME_FORMAT));

        $this->XLSXArchiver->saveMonthly($device,  $data, $entry, $date, $fileName);
        $archive = $this->PDFArchiver->saveMonthly($device, $data, $entry, $date, $fileName);

        $this->rawDataHandler->encrypt($this->deviceDataRawDataFactory->create($data, $entry, $date), $archive->getFullPathWithoutExtension());

        $archive = $this->deviceDataArchiveFactory->create($device, $date, $entry, $fileName, DeviceDataArchive::PERIOD_MONTH);

        $this->entityManager->persist($archive);
        
        // Only flush immediately if requested (for backward compatibility)
        if ($flushImmediately) {
            $this->entityManager->flush();
        }
    }

    private function generateFilename(string $identifier, $entry, $date): string
    {
        $text = sprintf('%s_t%s_%s', $identifier, $entry, $date);
        $text = preg_replace('/[^a-zA-Z0-9]+/u', '_', $text);

        // Trim and lowercase
        $text = trim($text, '-');
        return mb_strtolower($text, 'UTF-8');
    }

    private function getDates(?string $fromDate = null, ?string $toDate = null): \DatePeriod
    {
        if ($fromDate === null) {
            $fromDate = new \DateTime('-1 day');
            $toDate = new \DateTime();
        } else if ($toDate === null) {
            $fromDate = new \DateTime($fromDate);
            $toDate = new \DateTime();
        } else {
            $fromDate = new \DateTime($fromDate);
            $toDate = new \DateTime($toDate);
        }

        $fromDate->setTime(0, 0, 0);
        $toDate->setTime(0, 0, 0);

        return new \DatePeriod(
            $fromDate,
            new \DateInterval('P1D'),
            $toDate
        );
    }
    
    /**
     * Pre-check which archives already exist for a set of devices and a date
     * This is more efficient than checking one by one
     */
    private function preCheckExistingArchives(array $devices, \DateTime $date, string $period): array
    {
        $deviceIds = array_map(function (Device $device) {
            return $device->getId();
        }, $devices);
        
        // Get all existing archives for these devices on this date with this period
        $qb = $this->deviceDataArchiveRepository->createQueryBuilder('dda')
            ->where('dda.device IN (:device_ids)')
            ->andWhere('dda.period = :period')
            ->andWhere('dda.archiveDate = :archive_date')
            ->setParameter('device_ids', $deviceIds)
            ->setParameter('period', $period)
            ->setParameter('archive_date', $date);
            
        $existingArchives = $qb->getQuery()->getResult();
        
        // Create a lookup map for quick checking
        $archiveMap = [];
        foreach ($existingArchives as $archive) {
            $key = $this->getArchiveKey(
                $archive->getDevice()->getId(),
                $archive->getEntry(),
                $archive->getArchiveDate(),
                $archive->getPeriod()
            );
            $archiveMap[$key] = true;
        }
        
        return $archiveMap;
    }
    
    /**
     * Generate a unique key for an archive based on its properties
     */
    private function getArchiveKey(int $deviceId, int $entry, \DateTime $date, string $period): string
    {
        return sprintf('%d_%d_%s_%s', $deviceId, $entry, $date->format('Y-m-d'), $period);
    }
}
