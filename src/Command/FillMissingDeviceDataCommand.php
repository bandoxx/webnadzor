<?php

namespace App\Command;

use App\Entity\Device;
use App\Entity\DeviceData;
use App\Repository\DeviceDataRepository;
use App\Repository\DeviceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:fill-missing-device-data',
    description: 'Fills missing DeviceData entries by copying and slightly modifying past records',
)]
class FillMissingDeviceDataCommand extends Command
{
    private const BATCH_SIZE = 50;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly DeviceRepository $deviceRepository,
        private readonly DeviceDataRepository $deviceDataRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('from', null, InputOption::VALUE_REQUIRED, 'Start datetime (Y-m-d H:i:s)')
            ->addOption('to', null, InputOption::VALUE_REQUIRED, 'End datetime (Y-m-d H:i:s)')
            ->addOption('device-id', null, InputOption::VALUE_OPTIONAL, 'Specific device ID (optional)')
            ->addOption('interval', null, InputOption::VALUE_OPTIONAL, 'Expected interval in minutes between records', '10')
            ->addOption('lookback-days', null, InputOption::VALUE_OPTIONAL, 'Days to look back for similar records', '7')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Simulate without inserting data')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Parse and validate inputs
        $fromStr = $input->getOption('from');
        $toStr = $input->getOption('to');
        $deviceId = $input->getOption('device-id');
        $interval = (int) $input->getOption('interval');
        $lookbackDays = (int) $input->getOption('lookback-days');
        $dryRun = $input->getOption('dry-run');

        if (!$fromStr || !$toStr) {
            $io->error('Both --from and --to options are required');
            return Command::FAILURE;
        }

        try {
            $from = new \DateTime($fromStr);
            $to = new \DateTime($toStr);
        } catch (\Exception $e) {
            $io->error('Invalid datetime format. Use Y-m-d H:i:s format');
            return Command::FAILURE;
        }

        if ($from >= $to) {
            $io->error('From date must be before To date');
            return Command::FAILURE;
        }

        if ($dryRun) {
            $io->warning('DRY RUN MODE - No data will be inserted');
        }

        // Get devices to process
        $devices = $deviceId
            ? [$this->deviceRepository->find($deviceId)]
            : $this->deviceRepository->findActiveDevices();

        if (empty($devices) || (count($devices) === 1 && $devices[0] === null)) {
            $io->error('No devices found');
            return Command::FAILURE;
        }

        $io->title('Filling Missing DeviceData');
        $io->info(sprintf(
            'Period: %s to %s | Interval: %d minutes | Lookback: %d days | Devices: %d',
            $from->format('Y-m-d H:i:s'),
            $to->format('Y-m-d H:i:s'),
            $interval,
            $lookbackDays,
            count($devices)
        ));

        $totalInserted = 0;
        $totalSkipped = 0;

        foreach ($devices as $device) {
            if (!$device) {
                continue;
            }

            $io->section(sprintf('Processing Device: %s (ID: %d)', $device->getName(), $device->getId()));

            [$inserted, $skipped] = $this->processDevice(
                $device,
                $from,
                $to,
                $interval,
                $lookbackDays,
                $dryRun,
                $io
            );

            $totalInserted += $inserted;
            $totalSkipped += $skipped;
        }

        $io->success(sprintf(
            'Completed! Inserted: %d records | Skipped: %d records',
            $totalInserted,
            $totalSkipped
        ));

        return Command::SUCCESS;
    }

    private function processDevice(
        Device $device,
        \DateTime $from,
        \DateTime $to,
        int $intervalMinutes,
        int $lookbackDays,
        bool $dryRun,
        SymfonyStyle $io
    ): array {
        $inserted = 0;
        $skipped = 0;
        $batchCount = 0;

        // Generate expected timestamps
        $expectedTimestamps = $this->generateExpectedTimestamps($from, $to, $intervalMinutes);

        $io->progressStart(count($expectedTimestamps));

        foreach ($expectedTimestamps as $timestamp) {
            $io->progressAdvance();

            // Check if data already exists for this timestamp
            if ($this->recordExists($device, $timestamp)) {
                $skipped++;
                continue;
            }

            // Find similar record from the past (same time of day, different date)
            $templateRecord = $this->findSimilarPastRecord($device, $timestamp, $lookbackDays);

            if (!$templateRecord) {
                $skipped++;
                continue;
            }

            // Create modified record
            $newRecord = $this->createModifiedRecord($device, $templateRecord, $timestamp, $io);

            if (!$newRecord) {
                $skipped++;
                continue;
            }

            if (!$dryRun) {
                $this->entityManager->persist($newRecord);
                $batchCount++;

                // Flush in batches for performance
                if ($batchCount >= self::BATCH_SIZE) {
                    $this->entityManager->flush();
                    $this->entityManager->clear();
                    $batchCount = 0;
                }
            }

            $inserted++;
        }

        // Flush remaining records
        if (!$dryRun && $batchCount > 0) {
            $this->entityManager->flush();
            $this->entityManager->clear();
        }

        $io->progressFinish();

        return [$inserted, $skipped];
    }

    private function generateExpectedTimestamps(\DateTime $from, \DateTime $to, int $intervalMinutes): array
    {
        $timestamps = [];
        $current = clone $from;

        while ($current <= $to) {
            $timestamps[] = clone $current;
            $current->modify("+{$intervalMinutes} minutes");
        }

        return $timestamps;
    }

    private function recordExists(Device $device, \DateTime $timestamp): bool
    {
        // Check exact timestamp match on serverDate
        $qb = $this->entityManager->createQueryBuilder();

        $exactMatch = $qb->select('COUNT(dd.id)')
            ->from(DeviceData::class, 'dd')
            ->where('dd.device = :device')
            ->andWhere('dd.serverDate = :timestamp')
            ->setParameter('device', $device)
            ->setParameter('timestamp', $timestamp)
            ->getQuery()
            ->getSingleScalarResult();

        if ($exactMatch > 0) {
            return true;
        }

        // Also check deviceDate to be extra safe
        $qb = $this->entityManager->createQueryBuilder();

        $deviceDateMatch = $qb->select('COUNT(dd.id)')
            ->from(DeviceData::class, 'dd')
            ->where('dd.device = :device')
            ->andWhere('dd.deviceDate = :timestamp')
            ->setParameter('device', $device)
            ->setParameter('timestamp', $timestamp)
            ->getQuery()
            ->getSingleScalarResult();

        if ($deviceDateMatch > 0) {
            return true;
        }

        // Check for records within ±2 minutes window to prevent near-duplicates
        $before = (clone $timestamp)->modify('-2 minutes');
        $after = (clone $timestamp)->modify('+2 minutes');

        $qb = $this->entityManager->createQueryBuilder();

        $nearMatch = $qb->select('COUNT(dd.id)')
            ->from(DeviceData::class, 'dd')
            ->where('dd.device = :device')
            ->andWhere('dd.serverDate >= :before')
            ->andWhere('dd.serverDate <= :after')
            ->setParameter('device', $device)
            ->setParameter('before', $before)
            ->setParameter('after', $after)
            ->getQuery()
            ->getSingleScalarResult();

        return $nearMatch > 0;
    }

    private function findSimilarPastRecord(Device $device, \DateTime $targetTimestamp, int $lookbackDays): ?DeviceData
    {
        // Look for records with same time of day going back N days
        for ($daysBack = 1; $daysBack <= $lookbackDays; $daysBack++) {
            $pastDate = (clone $targetTimestamp)->modify("-{$daysBack} days");

            $qb = $this->entityManager->createQueryBuilder();

            $record = $qb->select('dd')
                ->from(DeviceData::class, 'dd')
                ->where('dd.device = :device')
                ->andWhere('dd.serverDate = :datetime')
                ->setParameter('device', $device)
                ->setParameter('datetime', $pastDate)
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();

            if ($record) {
                return $record;
            }
        }

        // If no exact time match found, try to find closest record from the lookback period
        $lookbackDate = (clone $targetTimestamp)->modify("-{$lookbackDays} days");

        $qb = $this->entityManager->createQueryBuilder();

        return $qb->select('dd')
            ->from(DeviceData::class, 'dd')
            ->where('dd.device = :device')
            ->andWhere('dd.serverDate >= :lookbackDate')
            ->andWhere('dd.serverDate < :targetDate')
            ->setParameter('device', $device)
            ->setParameter('lookbackDate', $lookbackDate)
            ->setParameter('targetDate', $targetTimestamp)
            ->orderBy('dd.serverDate', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    private function createModifiedRecord(
        Device $device,
        DeviceData $template,
        \DateTime $timestamp,
        SymfonyStyle $io
    ): ?DeviceData {
        $newRecord = new DeviceData();
        $newRecord->setDevice($device);
        $newRecord->setServerDate($timestamp);
        $newRecord->setDeviceDate($timestamp);

        // Copy and modify GSM signal (±1-3 points)
        $gsmSignal = $template->getGsmSignal();
        if ($gsmSignal !== null) {
            $newRecord->setGsmSignal($this->modifyInteger($gsmSignal, -3, 3, 0, 31));
        }

        // Copy supply status (no modification needed)
        $newRecord->setSupply($template->isSupply());

        // Copy and modify battery voltage (±0.01-0.05V)
        $vbat = $template->getVbat();
        if ($vbat !== null) {
            $newRecord->setVbat($this->modifyDecimal((float) $vbat, -0.05, 0.05, 0, 5.0));
        }

        // Copy and modify battery percentage (±1-2%)
        $battery = $template->getBattery();
        if ($battery !== null) {
            $newRecord->setBattery($this->modifyInteger($battery, -2, 2, 0, 100));
        }

        // Process both entries
        foreach ([1, 2] as $entry) {
            // Copy digital entry status (no modification)
            $dGetter = "getD{$entry}";
            $dSetter = "setD{$entry}";
            if (method_exists($template, $dGetter)) {
                $newRecord->$dSetter($template->$dGetter());
            }

            // Temperature - modify within safe range
            $tGetter = "getT{$entry}";
            $tSetter = "setT{$entry}";
            if (method_exists($template, $tGetter)) {
                $tempValue = $template->$tGetter();
                if ($tempValue !== null && $device->isTUsed($entry)) {
                    $entryData = $device->getEntryData($entry);
                    $tMin = (float) ($entryData['t_min'] ?? -99.99);
                    $tMax = (float) ($entryData['t_max'] ?? 99.99);

                    // Add small random variation but stay well within bounds
                    $safeMin = $tMin + 1.0; // Stay 1 degree away from alarm threshold
                    $safeMax = $tMax - 1.0;

                    $modified = $this->modifyDecimal(
                        (float) $tempValue,
                        -0.3,
                        0.3,
                        $safeMin,
                        $safeMax
                    );

                    $newRecord->$tSetter((string) $modified);
                }
            }

            // Humidity - modify within safe range
            $rhGetter = "getRh{$entry}";
            $rhSetter = "setRh{$entry}";
            if (method_exists($template, $rhGetter)) {
                $rhValue = $template->$rhGetter();
                if ($rhValue !== null && $device->isRhUsed($entry)) {
                    $entryData = $device->getEntryData($entry);
                    $rhMin = (float) ($entryData['rh_min'] ?? 0);
                    $rhMax = (float) ($entryData['rh_max'] ?? 100);

                    // Add small random variation but stay well within bounds
                    $safeMin = $rhMin + 2.0; // Stay 2% away from alarm threshold
                    $safeMax = $rhMax - 2.0;

                    $modified = $this->modifyDecimal(
                        (float) $rhValue,
                        -0.5,
                        0.5,
                        $safeMin,
                        $safeMax
                    );

                    $newRecord->$rhSetter((string) $modified);
                }
            }

            // Copy MKT values with slight modification
            $mktGetter = "getMkt{$entry}";
            $mktSetter = "setMkt{$entry}";
            if (method_exists($template, $mktGetter)) {
                $mktValue = $template->$mktGetter();
                if ($mktValue !== null) {
                    $modified = $this->modifyDecimal((float) $mktValue, -0.2, 0.2, -99.99, 99.99);
                    $newRecord->$mktSetter((string) $modified);
                }
            }

            // Copy average, min, max temperature values with slight modification
            foreach (['tAvrg', 'tMin', 'tMax'] as $field) {
                $getter = "get{$field}{$entry}";
                $setter = "set{$field}{$entry}";
                if (method_exists($template, $getter)) {
                    $value = $template->$getter();
                    if ($value !== null) {
                        $modified = $this->modifyDecimal((float) $value, -0.2, 0.2, -99.99, 99.99);
                        $newRecord->$setter((string) $modified);
                    }
                }
            }

            // Copy notes
            $noteGetter = "getNote{$entry}";
            $noteSetter = "setNote{$entry}";
            if (method_exists($template, $noteGetter)) {
                $newRecord->$noteSetter($template->$noteGetter());
            }
        }

        return $newRecord;
    }

    /**
     * Modify a decimal value by adding a random variation within range
     */
    private function modifyDecimal(
        float $value,
        float $minVariation,
        float $maxVariation,
        float $absoluteMin,
        float $absoluteMax
    ): float {
        // Generate random variation
        $variation = $minVariation + (mt_rand() / mt_getrandmax()) * ($maxVariation - $minVariation);
        $modified = $value + $variation;

        // Clamp to absolute bounds
        $modified = max($absoluteMin, min($absoluteMax, $modified));

        // Round to 2 decimal places
        return round($modified, 2);
    }

    /**
     * Modify an integer value by adding a random variation within range
     */
    private function modifyInteger(
        int $value,
        int $minVariation,
        int $maxVariation,
        int $absoluteMin,
        int $absoluteMax
    ): int {
        $variation = mt_rand($minVariation, $maxVariation);
        $modified = $value + $variation;

        // Clamp to absolute bounds
        return max($absoluteMin, min($absoluteMax, $modified));
    }
}
