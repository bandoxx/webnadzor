<?php

namespace App\Service\DeviceData;

use App\Entity\Device;
use App\Entity\DeviceData;
use App\Repository\DeviceDataArchiveRepository;
use App\Repository\DeviceDataRepository;
use App\Repository\DeviceRepository;
use Doctrine\ORM\EntityManagerInterface;

class ShiftDeviceDataService
{
    public function __construct(
        private readonly DeviceDataRepository $deviceDataRepository,
        private readonly DeviceRepository $deviceRepository,
        private readonly DeviceDataArchiveRepository $deviceDataArchiveRepository,
        private readonly DeviceDataDailyArchiveService $dailyArchiveService,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    private const MIN_INTERVAL_DAYS = 20;
    private const MAX_INTERVAL_DAYS = 35;

    /**
     * Extract temperature/humidity limits from device settings
     *
     * @param Device $device
     * @return array Limits array with t1_min, t1_max, t2_min, t2_max, rh1_min, rh1_max, rh2_min, rh2_max
     */
    private function extractLimitsFromDevice(Device $device): array
    {
        $entry1 = $device->getEntry1() ?? [];
        $entry2 = $device->getEntry2() ?? [];

        return [
            't1_min' => $entry1['t_min'] ?? null,
            't1_max' => $entry1['t_max'] ?? null,
            't2_min' => $entry2['t_min'] ?? null,
            't2_max' => $entry2['t_max'] ?? null,
            'rh1_min' => $entry1['rh_min'] ?? null,
            'rh1_max' => $entry1['rh_max'] ?? null,
            'rh2_min' => $entry2['rh_min'] ?? null,
            'rh2_max' => $entry2['rh_max'] ?? null,
        ];
    }

    /**
     * Check if any limits are configured
     */
    private function hasConfiguredLimits(array $limits): bool
    {
        return $limits['t1_min'] !== null
            || $limits['t1_max'] !== null
            || $limits['t2_min'] !== null
            || $limits['t2_max'] !== null;
    }

    /**
     * Find the best interval (20-35 days) with the most available records
     * Only counts records within device temperature/humidity limits
     *
     * @param int $deviceId
     * @param \DateTimeInterface $dateFrom
     * @param \DateTimeInterface $dateTo
     * @return array{intervalDays: int, recordCount: int} Best interval and its record count
     */
    public function findBestInterval(
        int $deviceId,
        \DateTimeInterface $dateFrom,
        \DateTimeInterface $dateTo
    ): array {
        $limits = null;
        $device = $this->deviceRepository->find($deviceId);
        if ($device) {
            $limits = $this->extractLimitsFromDevice($device);
            if (!$this->hasConfiguredLimits($limits)) {
                $limits = null;
            }
        }

        $bestInterval = self::MIN_INTERVAL_DAYS;
        $bestCount = 0;

        for ($interval = self::MIN_INTERVAL_DAYS; $interval <= self::MAX_INTERVAL_DAYS; $interval++) {
            $count = $this->deviceDataRepository->countShiftedDataForInterval(
                $deviceId,
                $dateFrom,
                $dateTo,
                $interval,
                $limits
            );

            if ($count > $bestCount) {
                $bestCount = $count;
                $bestInterval = $interval;
            }
        }

        return [
            'intervalDays' => $bestInterval,
            'recordCount' => $bestCount,
        ];
    }

    /**
     * Preview device data that would be inserted with shifted dates
     * Automatically finds the best interval (20-35 days) with most records
     * Always excludes records that exceed device temperature/humidity limits
     *
     * @param int $deviceId
     * @param \DateTimeInterface $dateFrom
     * @param \DateTimeInterface $dateTo
     * @param int|null $intervalDays If null, will find the best interval automatically
     * @return array{intervalDays: int, records: array, filteredCount: int} The interval used, preview records, and count of filtered alarm records
     */
    public function previewShiftedData(
        int $deviceId,
        \DateTimeInterface $dateFrom,
        \DateTimeInterface $dateTo,
        ?int $intervalDays = null
    ): array {
        $device = $this->deviceRepository->find($deviceId);
        $limits = null;

        if ($device) {
            $limits = $this->extractLimitsFromDevice($device);
            if (!$this->hasConfiguredLimits($limits)) {
                $limits = null;
            }
        }

        // If no interval specified, find the best one
        if ($intervalDays === null) {
            $bestMatch = $this->findBestInterval($deviceId, $dateFrom, $dateTo);
            $intervalDays = $bestMatch['intervalDays'];
        }

        // Get count without filter to calculate how many were filtered
        $unfilteredCount = $this->deviceDataRepository->countShiftedDataForInterval(
            $deviceId,
            $dateFrom,
            $dateTo,
            $intervalDays,
            null
        );

        $records = $this->deviceDataRepository->getShiftedDataPreview(
            $deviceId,
            $dateFrom,
            $dateTo,
            $intervalDays,
            $limits
        );

        $filteredCount = $unfilteredCount - count($records);

        return [
            'intervalDays' => $intervalDays,
            'records' => $records,
            'filteredCount' => $filteredCount,
            'limits' => $limits,
        ];
    }

    /**
     * Insert device data with shifted dates
     * Always filters out records that exceed device limits and finds alternatives
     *
     * @param int $deviceId
     * @param \DateTimeInterface $dateFrom
     * @param \DateTimeInterface $dateTo
     * @param int $intervalDays Number of days to shift (default 25)
     * @return array{inserted: int, filtered: int, alternatives: int} Counts of inserted, filtered, and alternative records
     * @throws \InvalidArgumentException
     */
    public function insertShiftedData(
        int $deviceId,
        \DateTimeInterface $dateFrom,
        \DateTimeInterface $dateTo,
        int $intervalDays = 25
    ): array {
        // Verify device exists
        $device = $this->deviceRepository->find($deviceId);
        if (!$device) {
            throw new \InvalidArgumentException("Device with ID {$deviceId} not found");
        }

        $limits = $this->extractLimitsFromDevice($device);
        if (!$this->hasConfiguredLimits($limits)) {
            $limits = null;
        }

        // Delete old daily archives for the full date range (from/to)
        $this->deviceDataArchiveRepository->deleteDailyArchivesForDeviceAndDateRange(
            $deviceId,
            $dateFrom,
            $dateTo
        );

        // 1. First insert all records that are within limits
        $insertedCount = $this->deviceDataRepository->insertShiftedData(
            $deviceId,
            $dateFrom,
            $dateTo,
            $intervalDays,
            $limits
        );

        $alternativesInserted = 0;
        $filteredCount = 0;

        // 2. If limits are configured, find records that were filtered out and try to find alternatives
        if ($limits !== null) {
            $alternativesInserted = $this->insertAlternativesForFilteredRecords(
                $device,
                $dateFrom,
                $dateTo,
                $intervalDays,
                $limits
            );

            // Calculate filtered count
            $unfilteredCount = $this->deviceDataRepository->countShiftedDataForInterval(
                $deviceId,
                $dateFrom,
                $dateTo,
                $intervalDays,
                null
            );

            $filteredCount = max(0, $unfilteredCount - $insertedCount - $alternativesInserted);
        }

        // Create new daily archives (excluding today if it's in the range)
        $this->dailyArchiveService->generateDailyArchivesForDateRange($device, $dateFrom, $dateTo);

        return [
            'inserted' => $insertedCount,
            'filtered' => $filteredCount,
            'alternatives' => $alternativesInserted,
        ];
    }

    /**
     * Find and insert alternative records for gaps left by filtered alarm records
     */
    private function insertAlternativesForFilteredRecords(
        Device $device,
        \DateTimeInterface $dateFrom,
        \DateTimeInterface $dateTo,
        int $intervalDays,
        array $limits
    ): int {
        $deviceId = $device->getId();
        $conn = $this->entityManager->getConnection();

        // Find target datetimes that still have gaps (no data exists)
        // These are records that were filtered out due to alarm values
        $sql = "
            SELECT
                DATE_ADD(dd.device_date, INTERVAL :intervalDays DAY) as target_datetime
            FROM device_data dd
            WHERE dd.device_id = :deviceId
              AND dd.device_date BETWEEN DATE_SUB(:dateFrom, INTERVAL :intervalDays DAY)
                                     AND DATE_SUB(:dateTo, INTERVAL :intervalDays DAY)
              AND NOT EXISTS (
                    SELECT 1
                    FROM device_data dd2
                    WHERE dd2.device_id = dd.device_id
                      AND dd2.device_date = DATE_ADD(dd.device_date, INTERVAL :intervalDays DAY)
                )
              AND (
                  (dd.t1 IS NOT NULL AND (dd.t1 < :t1Min OR dd.t1 > :t1Max))
                  OR (dd.t2 IS NOT NULL AND (dd.t2 < :t2Min OR dd.t2 > :t2Max))
              )
            ORDER BY dd.device_date
        ";

        $params = [
            'deviceId' => $deviceId,
            'dateFrom' => $dateFrom->format('Y-m-d H:i:s'),
            'dateTo' => $dateTo->format('Y-m-d H:i:s'),
            'intervalDays' => $intervalDays,
            't1Min' => $limits['t1_min'] ?? -999,
            't1Max' => $limits['t1_max'] ?? 999,
            't2Min' => $limits['t2_min'] ?? -999,
            't2Max' => $limits['t2_max'] ?? 999,
        ];

        $stmt = $conn->prepare($sql);
        $result = $stmt->executeQuery($params);
        $gapDatetimes = $result->fetchAllAssociative();

        $alternativesInserted = 0;

        foreach ($gapDatetimes as $gap) {
            $targetDateTime = new \DateTime($gap['target_datetime']);

            // Check if a record was already inserted for this datetime
            $existingRecord = $this->deviceDataRepository->findOneBy([
                'device' => $deviceId,
                'deviceDate' => $targetDateTime,
            ]);

            if ($existingRecord) {
                continue;
            }

            // Try to find an alternative record from nearby intervals
            $alternative = $this->deviceDataRepository->findAlternativeRecordWithinLimits(
                $deviceId,
                $targetDateTime,
                $intervalDays,
                $limits
            );

            if ($alternative) {
                // Insert the alternative record with the target datetime
                $this->insertAlternativeRecord($device, $alternative, $targetDateTime);
                $alternativesInserted++;
            }
        }

        return $alternativesInserted;
    }

    /**
     * Insert an alternative record with adjusted datetime
     */
    private function insertAlternativeRecord(
        Device $device,
        array $sourceData,
        \DateTimeInterface $targetDateTime
    ): void {
        $record = new DeviceData();
        $record->setDevice($device);
        $record->setServerDate($targetDateTime);
        $record->setDeviceDate($targetDateTime);

        // Copy all data fields from the alternative source
        $record->setGsmSignal($sourceData['gsm_signal'] ?? null);
        $record->setSupply($sourceData['supply'] ?? null);
        $record->setVbat($sourceData['vbat'] ?? null);
        $record->setBattery($sourceData['battery'] ?? null);
        $record->setD1($sourceData['d1'] ?? null);
        $record->setT1($sourceData['t1'] ?? null);
        $record->setRh1($sourceData['rh1'] ?? null);
        $record->setMkt1($sourceData['mkt1'] ?? null);
        $record->setTAvrg1($sourceData['t_avrg1'] ?? null);
        $record->setTMin1($sourceData['t_min1'] ?? null);
        $record->setTMax1($sourceData['t_max1'] ?? null);
        $record->setNote1($sourceData['note1'] ?? null);
        $record->setD2($sourceData['d2'] ?? null);
        $record->setT2($sourceData['t2'] ?? null);
        $record->setRh2($sourceData['rh2'] ?? null);
        $record->setMkt2($sourceData['mkt2'] ?? null);
        $record->setTAvrg2($sourceData['t_avrg2'] ?? null);
        $record->setTMin2($sourceData['t_min2'] ?? null);
        $record->setTMax2($sourceData['t_max2'] ?? null);
        $record->setNote2($sourceData['note2'] ?? null);

        $this->entityManager->persist($record);
        $this->entityManager->flush();
    }
}
