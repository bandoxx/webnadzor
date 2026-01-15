<?php

namespace App\Service\DeviceData;

use App\Repository\DeviceDataArchiveRepository;
use App\Repository\DeviceRepository;
use Doctrine\DBAL\Connection;

class ShiftDeviceDataService
{
    private const MIN_INTERVAL_DAYS = 20;
    private const MAX_INTERVAL_DAYS = 35;

    public function __construct(
        private readonly Connection $connection,
        private readonly DeviceRepository $deviceRepository,
        private readonly DeviceDataArchiveRepository $deviceDataArchiveRepository,
        private readonly DeviceDataDailyArchiveService $dailyArchiveService
    ) {
    }

    /**
     * Find the best interval (20-35 days) with the most available records
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
        $bestInterval = self::MIN_INTERVAL_DAYS;
        $bestCount = 0;

        for ($interval = self::MIN_INTERVAL_DAYS; $interval <= self::MAX_INTERVAL_DAYS; $interval++) {
            $count = $this->countShiftedDataForInterval(
                $deviceId,
                $dateFrom,
                $dateTo,
                $interval
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
     *
     * @param int $deviceId
     * @param \DateTimeInterface $dateFrom
     * @param \DateTimeInterface $dateTo
     * @param int|null $intervalDays If null, will find the best interval automatically
     * @return array{intervalDays: int, records: array} The interval used and preview records
     */
    public function previewShiftedData(
        int $deviceId,
        \DateTimeInterface $dateFrom,
        \DateTimeInterface $dateTo,
        ?int $intervalDays = null
    ): array {
        // If no interval specified, find the best one
        if ($intervalDays === null) {
            $bestMatch = $this->findBestInterval($deviceId, $dateFrom, $dateTo);
            $intervalDays = $bestMatch['intervalDays'];
        }

        $records = $this->getShiftedDataPreview(
            $deviceId,
            $dateFrom,
            $dateTo,
            $intervalDays
        );

        return [
            'intervalDays' => $intervalDays,
            'records' => $records,
        ];
    }

    /**
     * Insert device data with shifted dates
     *
     * @param int $deviceId
     * @param \DateTimeInterface $dateFrom
     * @param \DateTimeInterface $dateTo
     * @param int $intervalDays Number of days to shift (default 25)
     * @return int Number of records inserted
     * @throws \InvalidArgumentException
     */
    public function insertShiftedData(
        int $deviceId,
        \DateTimeInterface $dateFrom,
        \DateTimeInterface $dateTo,
        int $intervalDays = 25
    ): int {
        // Verify device exists
        $device = $this->deviceRepository->find($deviceId);
        if (!$device) {
            throw new \InvalidArgumentException("Device with ID {$deviceId} not found");
        }

        // Delete old daily archives for the full date range (from/to)
        $this->deviceDataArchiveRepository->deleteDailyArchivesForDeviceAndDateRange(
            $deviceId,
            $dateFrom,
            $dateTo
        );

        // Delete empty records (sensor errors) in target range before inserting
        // This allows replacement of empty logs with valid shifted data
        $this->deleteEmptyRecordsInRange(
            $deviceId,
            $dateFrom,
            $dateTo
        );

        // Insert shifted data
        $insertedCount = $this->insertShiftedDataRecords(
            $deviceId,
            $dateFrom,
            $dateTo,
            $intervalDays
        );

        // Create new daily archives (excluding today if it's in the range)
        $this->dailyArchiveService->generateDailyArchivesForDateRange($device, $dateFrom, $dateTo);

        return $insertedCount;
    }

    /**
     * Count available records for a specific interval shift
     *
     * @param int $deviceId
     * @param \DateTimeInterface $dateFrom Target date from
     * @param \DateTimeInterface $dateTo Target date to
     * @param int $intervalDays Number of days to shift forward from the past
     * @return int Number of available records for this interval
     */
    private function countShiftedDataForInterval(
        int $deviceId,
        \DateTimeInterface $dateFrom,
        \DateTimeInterface $dateTo,
        int $intervalDays
    ): int {
        return count($this->getShiftedDataPreview($deviceId, $dateFrom, $dateTo, $intervalDays));
    }

    /**
     * Preview device data that would be inserted with shifted dates
     * Fetches data from the past and shows what it would look like with target dates
     *
     * @param int $deviceId
     * @param \DateTimeInterface $dateFrom Target date from (where data should end up)
     * @param \DateTimeInterface $dateTo Target date to (where data should end up)
     * @param int $intervalDays Number of days to shift forward from the past
     * @return array<int, array<string, mixed>> Array of associative arrays with old and new dates plus all data
     */
    private function getShiftedDataPreview(
        int $deviceId,
        \DateTimeInterface $dateFrom,
        \DateTimeInterface $dateTo,
        int $intervalDays
    ): array {
        // Simple query to get source records
        $sql = '
            SELECT
                dd.id,
                dd.device_id,
                dd.server_date,
                dd.device_date,
                dd.gsm_signal,
                dd.supply,
                dd.vbat,
                dd.battery,
                dd.d1,
                dd.t1,
                dd.rh1,
                dd.mkt1,
                dd.t_avrg1,
                dd.t_min1,
                dd.t_max1,
                dd.note1,
                dd.d2,
                dd.t2,
                dd.rh2,
                dd.mkt2,
                dd.t_avrg2,
                dd.t_min2,
                dd.t_max2,
                dd.note2
            FROM device_data dd
            WHERE dd.device_id = :deviceId
              AND dd.device_date BETWEEN DATE_SUB(:dateFrom, INTERVAL :intervalDays DAY)
                                     AND DATE_SUB(:dateTo, INTERVAL :intervalDays DAY)
            ORDER BY dd.device_date
        ';

        $stmt = $this->connection->prepare($sql);
        $result = $stmt->executeQuery([
            'deviceId' => $deviceId,
            'dateFrom' => $dateFrom->format('Y-m-d H:i:s'),
            'dateTo' => $dateTo->format('Y-m-d H:i:s'),
            'intervalDays' => $intervalDays,
        ]);

        $sourceRecords = $result->fetchAllAssociative();

        if (empty($sourceRecords)) {
            return [];
        }

        // Get existing minutes in target period (hash map for O(1) lookup)
        $existingMinutes = $this->getExistingTargetMinutes($deviceId, $dateFrom, $dateTo);
        $intervalSeconds = $intervalDays * 86400;

        // Filter in PHP - only include records where target minute doesn't exist
        // Also track added minutes to avoid duplicates from source
        $filtered = [];
        $addedMinutes = [];
        foreach ($sourceRecords as $record) {
            $shiftedTimestamp = strtotime($record['device_date']) + $intervalSeconds;
            $shiftedMinuteKey = date('Y-m-d H:i', $shiftedTimestamp);

            // Skip if target minute already has data OR we already added a record for this minute
            if (!isset($existingMinutes[$shiftedMinuteKey]) && !isset($addedMinutes[$shiftedMinuteKey])) {
                $addedMinutes[$shiftedMinuteKey] = true;
                $filtered[] = [
                    'id' => $record['id'],
                    'device_id' => $record['device_id'],
                    'old_server_date' => $record['server_date'],
                    'old_device_date' => $record['device_date'],
                    'new_server_date' => date('Y-m-d H:i:s', strtotime($record['server_date']) + $intervalSeconds),
                    'new_device_date' => date('Y-m-d H:i:s', $shiftedTimestamp),
                    'gsm_signal' => $record['gsm_signal'],
                    'supply' => $record['supply'],
                    'vbat' => $record['vbat'],
                    'battery' => $record['battery'],
                    'd1' => $record['d1'],
                    't1' => $record['t1'],
                    'rh1' => $record['rh1'],
                    'mkt1' => $record['mkt1'],
                    't_avrg1' => $record['t_avrg1'],
                    't_min1' => $record['t_min1'],
                    't_max1' => $record['t_max1'],
                    'note1' => $record['note1'],
                    'd2' => $record['d2'],
                    't2' => $record['t2'],
                    'rh2' => $record['rh2'],
                    'mkt2' => $record['mkt2'],
                    't_avrg2' => $record['t_avrg2'],
                    't_min2' => $record['t_min2'],
                    't_max2' => $record['t_max2'],
                    'note2' => $record['note2'],
                ];
            }
        }

        return $filtered;
    }

    /**
     * Get existing record minutes in target period (for fast PHP comparison)
     * Returns array of "Y-m-d H:i" strings for quick lookup
     *
     * @param int $deviceId
     * @param \DateTimeInterface $dateFrom
     * @param \DateTimeInterface $dateTo
     * @return array<string, bool>
     */
    private function getExistingTargetMinutes(
        int $deviceId,
        \DateTimeInterface $dateFrom,
        \DateTimeInterface $dateTo
    ): array {
        // Fetch raw timestamps, format in PHP (faster than DATE_FORMAT in SQL)
        $sql = '
            SELECT dd.device_date
            FROM device_data dd
            WHERE dd.device_id = :deviceId
              AND dd.device_date BETWEEN :dateFrom AND :dateTo
              AND NOT ((dd.t1 IS NULL OR dd.t1 = 0) AND (dd.t2 IS NULL OR dd.t2 = 0))
        ';

        $stmt = $this->connection->prepare($sql);
        $result = $stmt->executeQuery([
            'deviceId' => $deviceId,
            'dateFrom' => $dateFrom->format('Y-m-d H:i:s'),
            'dateTo' => $dateTo->format('Y-m-d H:i:s'),
        ]);

        $minutes = [];
        foreach ($result->fetchAllAssociative() as $row) {
            $minutes[substr($row['device_date'], 0, 16)] = true; // "Y-m-d H:i"
        }

        return $minutes;
    }

    /**
     * Delete empty device data records (sensor errors) in the given date range.
     * A record is considered "empty" if both temperature values are NULL or 0.
     *
     * @param int $deviceId
     * @param \DateTimeInterface $dateFrom
     * @param \DateTimeInterface $dateTo
     * @return int Number of deleted records
     */
    private function deleteEmptyRecordsInRange(
        int $deviceId,
        \DateTimeInterface $dateFrom,
        \DateTimeInterface $dateTo
    ): int {
        $sql = '
            DELETE FROM device_data
            WHERE device_id = :deviceId
              AND device_date BETWEEN :dateFrom AND :dateTo
              AND (t1 IS NULL OR t1 = 0)
              AND (t2 IS NULL OR t2 = 0)
        ';

        $stmt = $this->connection->prepare($sql);
        return $stmt->executeStatement([
            'deviceId' => $deviceId,
            'dateFrom' => $dateFrom->format('Y-m-d H:i:s'),
            'dateTo' => $dateTo->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Insert device data with shifted dates
     * Uses preview results for consistent filtering
     *
     * @param int $deviceId
     * @param \DateTimeInterface $dateFrom Target date from (where data should end up)
     * @param \DateTimeInterface $dateTo Target date to (where data should end up)
     * @param int $intervalDays Number of days to shift forward from the past
     * @return int Number of records inserted
     */
    private function insertShiftedDataRecords(
        int $deviceId,
        \DateTimeInterface $dateFrom,
        \DateTimeInterface $dateTo,
        int $intervalDays
    ): int {
        $records = $this->getShiftedDataPreview($deviceId, $dateFrom, $dateTo, $intervalDays);

        if (empty($records)) {
            return 0;
        }

        $sql = '
            INSERT INTO device_data (
                device_id, server_date, device_date, gsm_signal, supply, vbat, battery,
                d1, t1, rh1, mkt1, t_avrg1, t_min1, t_max1, note1,
                d2, t2, rh2, mkt2, t_avrg2, t_min2, t_max2, note2
            ) VALUES (
                :device_id, :server_date, :device_date, :gsm_signal, :supply, :vbat, :battery,
                :d1, :t1, :rh1, :mkt1, :t_avrg1, :t_min1, :t_max1, :note1,
                :d2, :t2, :rh2, :mkt2, :t_avrg2, :t_min2, :t_max2, :note2
            )
        ';

        $stmt = $this->connection->prepare($sql);
        $insertedCount = 0;

        foreach ($records as $record) {
            $stmt->executeStatement([
                'device_id' => $record['device_id'],
                'server_date' => $record['new_server_date'],
                'device_date' => $record['new_device_date'],
                'gsm_signal' => $record['gsm_signal'],
                'supply' => $record['supply'],
                'vbat' => $record['vbat'],
                'battery' => $record['battery'],
                'd1' => $record['d1'],
                't1' => $record['t1'],
                'rh1' => $record['rh1'],
                'mkt1' => $record['mkt1'],
                't_avrg1' => $record['t_avrg1'],
                't_min1' => $record['t_min1'],
                't_max1' => $record['t_max1'],
                'note1' => $record['note1'],
                'd2' => $record['d2'],
                't2' => $record['t2'],
                'rh2' => $record['rh2'],
                'mkt2' => $record['mkt2'],
                't_avrg2' => $record['t_avrg2'],
                't_min2' => $record['t_min2'],
                't_max2' => $record['t_max2'],
                'note2' => $record['note2'],
            ]);
            $insertedCount++;
        }

        return $insertedCount;
    }
}
