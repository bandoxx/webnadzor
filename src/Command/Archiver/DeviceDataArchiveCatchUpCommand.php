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
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Command to find and regenerate missing daily archives.
 *
 * Use this command to:
 * - Detect gaps in daily archive generation
 * - Regenerate missing archives for specific date ranges
 * - Fix issues where one entry has archives but another doesn't
 *
 * Example usage:
 *   bin/console app:archive:catch-up --fromDate=2026-01-01 --toDate=2026-01-23
 *   bin/console app:archive:catch-up --fromDate=2026-01-01 --toDate=2026-01-23 --deviceId=123
 *   bin/console app:archive:catch-up --fromDate=2026-01-01 --toDate=2026-01-23 --dry-run
 */
#[AsCommand(
    name: 'app:archive:catch-up',
    description: 'Find and regenerate missing daily archives for devices.',
)]
class DeviceDataArchiveCatchUpCommand extends Command
{
    public function __construct(
        private readonly DeviceRepository $deviceRepository,
        private readonly DeviceDataRepository $deviceDataRepository,
        private readonly DeviceDataArchiveRepository $deviceDataArchiveRepository,
        private readonly ChartImageGenerator $chartImageGenerator,
        private readonly DeviceDataDailyArchiveService $dailyArchiveService,
        private readonly EntityManagerInterface $entityManager,
        private readonly ?LoggerInterface $logger = null
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('fromDate', null, InputOption::VALUE_REQUIRED, 'Start date (Y-m-d format)', null)
            ->addOption('toDate', null, InputOption::VALUE_REQUIRED, 'End date (Y-m-d format)', null)
            ->addOption('deviceId', null, InputOption::VALUE_OPTIONAL, 'Specific device ID (optional)', null)
            ->addOption('clientId', null, InputOption::VALUE_OPTIONAL, 'Specific client ID (optional)', null)
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Only report missing archives without generating them')
            ->addOption('entry', null, InputOption::VALUE_OPTIONAL, 'Specific entry (1 or 2, optional)', null)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $startTime = new \DateTime();

        $io->title('Daily Archive Catch-Up');
        $io->writeln(sprintf("[%s] Starting catch-up process", $startTime->format('Y-m-d H:i:s')));

        // Validate and parse inputs
        $fromDateStr = $input->getOption('fromDate');
        $toDateStr = $input->getOption('toDate');
        $deviceId = $input->getOption('deviceId');
        $clientId = $input->getOption('clientId');
        $dryRun = $input->getOption('dry-run');
        $entryFilter = $input->getOption('entry');

        if (!$fromDateStr || !$toDateStr) {
            $io->error('Both --fromDate and --toDate are required');
            return Command::FAILURE;
        }

        try {
            $fromDate = new \DateTime($fromDateStr);
            $toDate = new \DateTime($toDateStr);
        } catch (\Exception $e) {
            $io->error('Invalid date format. Use Y-m-d (e.g., 2026-01-01)');
            return Command::FAILURE;
        }

        // Exclude today (archive is generated next day)
        $today = (new \DateTime())->setTime(0, 0, 0);
        if ($toDate >= $today) {
            $toDate = (clone $today)->modify('-1 day');
            $io->note(sprintf('Adjusted toDate to %s (excluding today)', $toDate->format('Y-m-d')));
        }

        if ($fromDate > $toDate) {
            $io->error('fromDate must be before or equal to toDate');
            return Command::FAILURE;
        }

        // Validate entry filter
        $entries = Device::SENSOR_ENTRIES;
        if ($entryFilter !== null) {
            $entryFilter = (int) $entryFilter;
            if (!in_array($entryFilter, [1, 2], true)) {
                $io->error('Entry must be 1 or 2');
                return Command::FAILURE;
            }
            $entries = [$entryFilter];
        }

        // Get devices
        if ($deviceId) {
            $device = $this->deviceRepository->find($deviceId);
            if (!$device) {
                $io->error(sprintf('Device with ID %d not found', $deviceId));
                return Command::FAILURE;
            }
            $devices = [$device];
        } elseif ($clientId) {
            $devices = $this->deviceRepository->findBy(['client' => $clientId]);
            if (empty($devices)) {
                $io->error(sprintf('No devices found for client ID %d', $clientId));
                return Command::FAILURE;
            }
        } else {
            $devices = $this->deviceRepository->findDevicesWithIdentifiers();
        }

        $io->writeln(sprintf("Checking %d devices from %s to %s", count($devices), $fromDate->format('Y-m-d'), $toDate->format('Y-m-d')));

        if ($dryRun) {
            $io->note('DRY RUN MODE - No archives will be generated');
        }

        // Generate date list
        $dates = $this->getDatesInRange($fromDate, $toDate);
        $totalDates = count($dates);

        $io->writeln(sprintf("Checking %d dates", $totalDates));

        // Track statistics
        $stats = [
            'devicesProcessed' => 0,
            'archivesChecked' => 0,
            'archivesMissing' => 0,
            'archivesGenerated' => 0,
            'archivesSkipped' => 0,
            'errors' => 0,
            'missingDetails' => [],
        ];

        $io->progressStart(count($devices));

        foreach ($devices as $device) {
            $stats['devicesProcessed']++;

            // Get existing archives for this device in the date range
            $existingArchives = $this->getExistingArchivesMap($device, $fromDate, $toDate);

            foreach ($dates as $date) {
                foreach ($entries as $entry) {
                    $stats['archivesChecked']++;
                    $archiveKey = $this->getArchiveKey($device->getId(), $entry, $date);

                    if (isset($existingArchives[$archiveKey])) {
                        // Archive exists, skip
                        continue;
                    }

                    // Archive is missing
                    $stats['archivesMissing']++;
                    $stats['missingDetails'][] = [
                        'device_id' => $device->getId(),
                        'device_name' => $device->getName(),
                        'entry' => $entry,
                        'date' => $date->format('Y-m-d'),
                    ];

                    if ($dryRun) {
                        $stats['archivesSkipped']++;
                        continue;
                    }

                    // Generate the missing archive
                    try {
                        $this->generateMissingArchive($device, $date, $entry);
                        $stats['archivesGenerated']++;

                        $this->logger?->info('Generated missing archive', [
                            'device_id' => $device->getId(),
                            'entry' => $entry,
                            'date' => $date->format('Y-m-d'),
                        ]);
                    } catch (\Throwable $e) {
                        $stats['errors']++;
                        $io->warning(sprintf(
                            'Failed to generate archive for Device %d, Entry %d, Date %s: %s',
                            $device->getId(),
                            $entry,
                            $date->format('Y-m-d'),
                            $e->getMessage()
                        ));

                        $this->logger?->error('Failed to generate archive', [
                            'device_id' => $device->getId(),
                            'entry' => $entry,
                            'date' => $date->format('Y-m-d'),
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }

            // Clear entity manager periodically to free memory
            $this->entityManager->clear();
            gc_collect_cycles();

            $io->progressAdvance();
        }

        $io->progressFinish();

        // Output summary
        $endTime = new \DateTime();
        $duration = $startTime->diff($endTime);

        $io->newLine();
        $io->section('Summary');

        $io->table(
            ['Metric', 'Value'],
            [
                ['Devices Processed', $stats['devicesProcessed']],
                ['Archives Checked', $stats['archivesChecked']],
                ['Archives Missing', $stats['archivesMissing']],
                ['Archives Generated', $stats['archivesGenerated']],
                ['Archives Skipped (dry-run)', $stats['archivesSkipped']],
                ['Errors', $stats['errors']],
                ['Duration', $duration->format('%H:%I:%S')],
            ]
        );

        // Show first 20 missing archives if in dry-run mode
        if ($dryRun && !empty($stats['missingDetails'])) {
            $io->section('Missing Archives (first 50)');
            $io->table(
                ['Device ID', 'Device Name', 'Entry', 'Date'],
                array_slice(array_map(
                    fn($d) => [$d['device_id'], $d['device_name'], $d['entry'], $d['date']],
                    $stats['missingDetails']
                ), 0, 50)
            );

            if (count($stats['missingDetails']) > 50) {
                $io->note(sprintf('... and %d more missing archives', count($stats['missingDetails']) - 50));
            }
        }

        if ($stats['archivesMissing'] === 0) {
            $io->success('No missing archives found!');
        } elseif ($dryRun) {
            $io->warning(sprintf(
                'Found %d missing archives. Run without --dry-run to generate them.',
                $stats['archivesMissing']
            ));
        } else {
            $io->success(sprintf(
                'Generated %d archives. %d errors occurred.',
                $stats['archivesGenerated'],
                $stats['errors']
            ));
        }

        $io->writeln(sprintf("[%s] Catch-up process completed", $endTime->format('Y-m-d H:i:s')));

        return $stats['errors'] > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * Get all dates in a range (inclusive)
     *
     * @return \DateTime[]
     */
    private function getDatesInRange(\DateTime $fromDate, \DateTime $toDate): array
    {
        $from = (clone $fromDate)->setTime(0, 0, 0);
        $to = (clone $toDate)->setTime(0, 0, 0);

        // Add 1 day to include the end date
        $period = new \DatePeriod(
            $from,
            new \DateInterval('P1D'),
            (clone $to)->modify('+1 day')
        );

        return iterator_to_array($period);
    }

    /**
     * Get existing archives as a lookup map
     *
     * @return array<string, true>
     */
    private function getExistingArchivesMap(Device $device, \DateTime $fromDate, \DateTime $toDate): array
    {
        $qb = $this->deviceDataArchiveRepository->createQueryBuilder('dda')
            ->select('dda.entry, dda.archiveDate')
            ->where('dda.device = :device_id')
            ->andWhere('dda.period = :period')
            ->andWhere('dda.archiveDate >= :date_from')
            ->andWhere('dda.archiveDate <= :date_to')
            ->setParameter('device_id', $device->getId())
            ->setParameter('period', DeviceDataArchive::PERIOD_DAY)
            ->setParameter('date_from', $fromDate)
            ->setParameter('date_to', $toDate);

        $results = $qb->getQuery()->getArrayResult();

        $map = [];
        foreach ($results as $row) {
            $key = $this->getArchiveKey(
                $device->getId(),
                $row['entry'],
                $row['archiveDate']
            );
            $map[$key] = true;
        }

        return $map;
    }

    /**
     * Generate a unique key for archive lookup
     */
    private function getArchiveKey(int $deviceId, int $entry, \DateTimeInterface $date): string
    {
        return sprintf('%d_%d_%s', $deviceId, $entry, $date->format('Y-m-d'));
    }

    /**
     * Generate a single missing archive with proper error handling
     */
    private function generateMissingArchive(Device $device, \DateTime $date, int $entry): void
    {
        // Get device data for this day
        $data = $this->deviceDataRepository->findByDeviceAndForDay($device, $date);

        // Generate chart image
        $fromDate = (clone $date)->setTime(0, 0, 0);
        $toDate = (clone $date)->setTime(23, 59, 59);

        $this->chartImageGenerator->generateTemperatureAndHumidityChartImage(
            $device,
            $entry,
            $fromDate,
            $toDate
        );

        // Generate the daily report (with immediate flush)
        $this->dailyArchiveService->generateDailyReport($device, $data, $entry, $date, true);
    }
}
