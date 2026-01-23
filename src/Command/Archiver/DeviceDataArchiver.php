<?php

namespace App\Command\Archiver;

use App\Entity\Device;
use App\Entity\DeviceDataArchive;
use App\Repository\DeviceDataArchiveRepository;
use App\Repository\DeviceDataRepository;
use App\Repository\DeviceRepository;
use App\Service\Chart\ChartImageGenerator;
use App\Service\DeviceData\DeviceDataDailyArchiveService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:device-data-archiver:daily',
    description: 'Command for making XLSX and PDF archives on daily level.',
)]
class DeviceDataArchiver extends Command
{
    public function __construct(
        private DeviceRepository             $deviceRepository,
        private DeviceDataRepository         $deviceDataRepository,
        private DeviceDataArchiveRepository  $deviceDataArchiveRepository,
        private ChartImageGenerator          $chartImageGenerator,
        private DeviceDataDailyArchiveService $dailyArchiveService,
        private EntityManagerInterface       $entityManager,
        private ?LoggerInterface             $logger = null
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('fromDate', null, InputOption::VALUE_OPTIONAL, 'From date', null)
            ->addOption('toDate', null, InputOption::VALUE_OPTIONAL, 'To date', null)
            ->addOption('deviceId', null, InputOption::VALUE_OPTIONAL, 'Device id', null)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln(sprintf("%s - %s started", (new \DateTime())->format('Y-m-d H:i:s'), $this->getName()));

        if ($deviceId = $input->getOption('deviceId')) {
            $devices[] = $this->deviceRepository->find($deviceId);
        } else {
            $devices = $this->deviceRepository->findDevicesWithIdentifiers();
        }

        $dates = $this->getDates($input->getOption('fromDate'), $input->getOption('toDate'));

        // Batch size for flushing to database
        $batchSize = 20;
        $archiveCount = 0;
        $errorCount = 0;

        foreach ($dates as $date) {
            // Pre-check which archives already exist to avoid unnecessary processing
            $existingDailyArchives = $this->preCheckExistingArchives($devices, $date, DeviceDataArchive::PERIOD_DAY);

            foreach ($devices as $device) {
                $data = $this->deviceDataRepository->findByDeviceAndForDay($device, $date);
                foreach (Device::SENSOR_ENTRIES as $entry) {
                    $fromDate = (clone $date)->setTime(0, 0, 0);
                    $toDate = (clone $date)->setTime(23, 59, 59);

                    // Check if archive already exists using the pre-fetched data
                    $archiveKey = $this->getArchiveKey($device->getId(), $entry, $date, DeviceDataArchive::PERIOD_DAY);
                    if (isset($existingDailyArchives[$archiveKey])) {
                        continue;
                    }

                    // Wrap in try-catch to ensure Entry 2 is processed even if Entry 1 fails
                    try {
                        $this->chartImageGenerator->generateTemperatureAndHumidityChartImage($device, $entry, $fromDate, $toDate);
                        $this->dailyArchiveService->generateDailyReport($device, $data, $entry, $date, false);

                        $archiveCount++;
                        // Flush every $batchSize archives
                        if ($archiveCount % $batchSize === 0) {
                            $this->entityManager->flush();
                        }
                    } catch (\Throwable $e) {
                        $errorCount++;
                        $errorMsg = sprintf(
                            'Failed to generate archive for Device %d, Entry %d, Date %s: %s',
                            $device->getId(),
                            $entry,
                            $date->format('Y-m-d'),
                            $e->getMessage()
                        );
                        $output->writeln("<error>$errorMsg</error>");

                        $this->logger?->error('Archive generation failed', [
                            'device_id' => $device->getId(),
                            'entry' => $entry,
                            'date' => $date->format('Y-m-d'),
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                        ]);

                        // Continue to next entry - don't let Entry 1 failure prevent Entry 2
                    }
                }

                // Detach only DeviceData entities to free memory
                foreach ($data as $row) {
                    $this->entityManager->detach($row);
                }

                unset($data);
                gc_collect_cycles();
            }
        }

        // Final flush for any remaining archives
        if ($archiveCount % $batchSize !== 0) {
            $this->entityManager->flush();
        }

        $output->writeln(sprintf(
            "%s - %s finished. Archives: %d, Errors: %d",
            (new \DateTime())->format('Y-m-d H:i:s'),
            $this->getName(),
            $archiveCount,
            $errorCount
        ));

        return $errorCount > 0 ? Command::FAILURE : Command::SUCCESS;
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
