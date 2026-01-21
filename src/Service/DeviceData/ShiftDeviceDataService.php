<?php

namespace App\Service\DeviceData;

use App\Repository\DeviceDataArchiveRepository;
use App\Repository\DeviceRepository;
use Doctrine\DBAL\Connection;

class ShiftDeviceDataService
{
    // Search range for finding source data to fill gaps
    // Min 7 days: avoid copying very recent data that might have same issues
    // Max 21 days: search up to 3 weeks back for suitable source data
    private const MIN_INTERVAL_DAYS = 7;
    private const MAX_INTERVAL_DAYS = 21;

    // Entry constants
    public const ENTRY_BOTH = null;
    public const ENTRY_1 = 1;
    public const ENTRY_2 = 2;

    public function __construct(
        private readonly Connection $connection,
        private readonly DeviceRepository $deviceRepository,
        private readonly DeviceDataArchiveRepository $deviceDataArchiveRepository,
        private readonly DeviceDataDailyArchiveService $dailyArchiveService
    ) {
    }

    /**
     * Find the best interval (7-21 days) with the most available records
     *
     * @param int $deviceId
     * @param \DateTimeInterface $dateFrom
     * @param \DateTimeInterface $dateTo
     * @param int|null $entry Entry number (1 or 2) for per-entry filling, null for both entries
     * @return array{intervalDays: int, recordCount: int} Best interval and its record count
     */
    public function findBestInterval(
        int $deviceId,
        \DateTimeInterface $dateFrom,
        \DateTimeInterface $dateTo,
        ?int $entry = null
    ): array {
        $bestInterval = self::MIN_INTERVAL_DAYS;
        $bestCount = 0;

        for ($interval = self::MIN_INTERVAL_DAYS; $interval <= self::MAX_INTERVAL_DAYS; $interval++) {
            $count = $this->countShiftedDataForInterval(
                $deviceId,
                $dateFrom,
                $dateTo,
                $interval,
                $entry
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
     * Automatically finds the best interval (7-21 days) with most records
     *
     * @param int $deviceId
     * @param \DateTimeInterface $dateFrom
     * @param \DateTimeInterface $dateTo
     * @param int|null $intervalDays If null, will find the best interval automatically
     * @param int|null $entry Entry number (1 or 2) for per-entry filling, null for both entries
     * @return array{intervalDays: int, records: array} The interval used and preview records
     */
    public function previewShiftedData(
        int $deviceId,
        \DateTimeInterface $dateFrom,
        \DateTimeInterface $dateTo,
        ?int $intervalDays = null,
        ?int $entry = null
    ): array {
        // If no interval specified, find the best one
        if ($intervalDays === null) {
            $bestMatch = $this->findBestInterval($deviceId, $dateFrom, $dateTo, $entry);
            $intervalDays = $bestMatch['intervalDays'];
        }

        $records = $this->getShiftedDataPreview(
            $deviceId,
            $dateFrom,
            $dateTo,
            $intervalDays,
            $entry
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
     * @param int|null $entry Entry number (1 or 2) for per-entry filling, null for both entries
     * @return int Number of records inserted/updated
     * @throws \InvalidArgumentException
     */
    public function insertShiftedData(
        int $deviceId,
        \DateTimeInterface $dateFrom,
        \DateTimeInterface $dateTo,
        int $intervalDays = 25,
        ?int $entry = null
    ): int {
        // Verify device exists
        $device = $this->deviceRepository->find($deviceId);
        if (!$device) {
            throw new \InvalidArgumentException("Device with ID {$deviceId} not found");
        }

        // Wrap in transaction for data consistency
        $this->connection->beginTransaction();

        try {
            // Delete old daily archives for the full date range (from/to)
            $this->deviceDataArchiveRepository->deleteDailyArchivesForDeviceAndDateRange(
                $deviceId,
                $dateFrom,
                $dateTo
            );

            // Insert new records or update existing empty records (no deletes)
            $count = $this->insertShiftedDataRecords(
                $deviceId,
                $dateFrom,
                $dateTo,
                $intervalDays,
                $entry
            );

            // Create new daily archives (excluding today if it's in the range)
            $this->dailyArchiveService->generateDailyArchivesForDateRange($device, $dateFrom, $dateTo);

            $this->connection->commit();

            return $count;
        } catch (\Exception $e) {
            $this->connection->rollBack();
            throw $e;
        }
    }

    /**
     * Count available records for a specific interval shift
     *
     * @param int $deviceId
     * @param \DateTimeInterface $dateFrom Target date from
     * @param \DateTimeInterface $dateTo Target date to
     * @param int $intervalDays Number of days to shift forward from the past
     * @param int|null $entry Entry number (1 or 2) for per-entry filling, null for both entries
     * @return int Number of available records for this interval
     */
    private function countShiftedDataForInterval(
        int $deviceId,
        \DateTimeInterface $dateFrom,
        \DateTimeInterface $dateTo,
        int $intervalDays,
        ?int $entry = null
    ): int {
        return count($this->getShiftedDataPreview($deviceId, $dateFrom, $dateTo, $intervalDays, $entry));
    }

    /**
     * Preview device data that would be inserted with shifted dates
     * Fetches data from the past and shows what it would look like with target dates
     *
     * @param int $deviceId
     * @param \DateTimeInterface $dateFrom Target date from (where data should end up)
     * @param \DateTimeInterface $dateTo Target date to (where data should end up)
     * @param int $intervalDays Number of days to shift forward from the past
     * @param int|null $entry Entry number (1 or 2) for per-entry filling, null for both entries
     * @return array<int, array<string, mixed>> Array of associative arrays with old and new dates plus all data
     */
    private function getShiftedDataPreview(
        int $deviceId,
        \DateTimeInterface $dateFrom,
        \DateTimeInterface $dateTo,
        int $intervalDays,
        ?int $entry = null
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
        // Also get records that exist but have empty entry data (for updates)
        $existingMinutes = $this->getExistingTargetMinutes($deviceId, $dateFrom, $dateTo, $entry);
        $existingRecordsWithEmptyEntry = $this->getExistingRecordsWithEmptyEntry($deviceId, $dateFrom, $dateTo, $entry);
        $intervalSeconds = $intervalDays * 86400;

        // Filter in PHP - only include records where target minute doesn't exist
        // Also track added minutes to avoid duplicates from source
        $filtered = [];
        $addedMinutes = [];
        foreach ($sourceRecords as $record) {
            // For per-entry filling, skip source records where the specific entry has no data
            if ($entry !== null) {
                $entryField = "t{$entry}";
                if ($record[$entryField] === null) {
                    continue;
                }
            }

            $shiftedTimestamp = strtotime($record['device_date']) + $intervalSeconds;
            $shiftedMinuteKey = date('Y-m-d H:i', $shiftedTimestamp);

            // Skip if target minute already has valid data for the entry OR we already added a record for this minute
            if (!isset($existingMinutes[$shiftedMinuteKey]) && !isset($addedMinutes[$shiftedMinuteKey])) {
                $addedMinutes[$shiftedMinuteKey] = true;

                // Check if this is an update (record exists but entry is empty) or insert (no record)
                $existingRecordId = $existingRecordsWithEmptyEntry[$shiftedMinuteKey] ?? null;

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
                    'existing_record_id' => $existingRecordId,
                    'operation' => $existingRecordId !== null ? 'update' : 'insert',
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
     * @param int|null $entry Entry number (1 or 2) for per-entry check, null for both entries
     * @return array<string, bool>
     */
    private function getExistingTargetMinutes(
        int $deviceId,
        \DateTimeInterface $dateFrom,
        \DateTimeInterface $dateTo,
        ?int $entry = null
    ): array {
        // Build the condition based on entry parameter
        // For per-entry: check only that specific entry has valid data
        // For both entries (null): check that at least one entry has valid data
        if ($entry === 1) {
            $validDataCondition = 'AND dd.t1 IS NOT NULL';
        } elseif ($entry === 2) {
            $validDataCondition = 'AND dd.t2 IS NOT NULL';
        } else {
            $validDataCondition = 'AND (dd.t1 IS NOT NULL OR dd.t2 IS NOT NULL)';
        }

        $sql = "
            SELECT dd.device_date
            FROM device_data dd
            WHERE dd.device_id = :deviceId
              AND dd.device_date BETWEEN :dateFrom AND :dateTo
              {$validDataCondition}
        ";

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
     * Get existing records where the specified entry is empty (for UPDATE operations)
     * Returns array of "Y-m-d H:i" => record_id for records that exist but have empty entry
     *
     * @param int $deviceId
     * @param \DateTimeInterface $dateFrom
     * @param \DateTimeInterface $dateTo
     * @param int|null $entry Entry number (1 or 2), or null for both entries empty
     * @return array<string, int>
     */
    private function getExistingRecordsWithEmptyEntry(
        int $deviceId,
        \DateTimeInterface $dateFrom,
        \DateTimeInterface $dateTo,
        ?int $entry
    ): array {
        // Get records where the specific entry is empty but record exists
        if ($entry === 1) {
            $emptyCondition = 'dd.t1 IS NULL';
        } elseif ($entry === 2) {
            $emptyCondition = 'dd.t2 IS NULL';
        } else {
            // Both entries must be empty for "both entries" mode
            $emptyCondition = 'dd.t1 IS NULL AND dd.t2 IS NULL';
        }

        $sql = "
            SELECT dd.id, dd.device_date
            FROM device_data dd
            WHERE dd.device_id = :deviceId
              AND dd.device_date BETWEEN :dateFrom AND :dateTo
              AND {$emptyCondition}
        ";

        $stmt = $this->connection->prepare($sql);
        $result = $stmt->executeQuery([
            'deviceId' => $deviceId,
            'dateFrom' => $dateFrom->format('Y-m-d H:i:s'),
            'dateTo' => $dateTo->format('Y-m-d H:i:s'),
        ]);

        $records = [];
        foreach ($result->fetchAllAssociative() as $row) {
            $records[substr($row['device_date'], 0, 16)] = (int) $row['id']; // "Y-m-d H:i" => id
        }

        return $records;
    }

    /**
     * Insert device data with shifted dates
     * Uses preview results for consistent filtering
     *
     * @param int $deviceId
     * @param \DateTimeInterface $dateFrom Target date from (where data should end up)
     * @param \DateTimeInterface $dateTo Target date to (where data should end up)
     * @param int $intervalDays Number of days to shift forward from the past
     * @param int|null $entry Entry number (1 or 2) for per-entry filling, null for both entries
     * @return int Number of records inserted/updated
     */
    private function insertShiftedDataRecords(
        int $deviceId,
        \DateTimeInterface $dateFrom,
        \DateTimeInterface $dateTo,
        int $intervalDays,
        ?int $entry = null
    ): int {
        $records = $this->getShiftedDataPreview($deviceId, $dateFrom, $dateTo, $intervalDays, $entry);

        if (empty($records)) {
            return 0;
        }

        $insertSql = '
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

        // Update SQL for entry 1 only
        $updateSql1 = '
            UPDATE device_data SET
                d1 = :d1, t1 = :t1, rh1 = :rh1, mkt1 = :mkt1,
                t_avrg1 = :t_avrg1, t_min1 = :t_min1, t_max1 = :t_max1, note1 = :note1
            WHERE id = :id
        ';

        // Update SQL for entry 2 only
        $updateSql2 = '
            UPDATE device_data SET
                d2 = :d2, t2 = :t2, rh2 = :rh2, mkt2 = :mkt2,
                t_avrg2 = :t_avrg2, t_min2 = :t_min2, t_max2 = :t_max2, note2 = :note2
            WHERE id = :id
        ';

        // Update SQL for both entries
        $updateSqlBoth = '
            UPDATE device_data SET
                server_date = :server_date,
                gsm_signal = :gsm_signal, supply = :supply, vbat = :vbat, battery = :battery,
                d1 = :d1, t1 = :t1, rh1 = :rh1, mkt1 = :mkt1,
                t_avrg1 = :t_avrg1, t_min1 = :t_min1, t_max1 = :t_max1, note1 = :note1,
                d2 = :d2, t2 = :t2, rh2 = :rh2, mkt2 = :mkt2,
                t_avrg2 = :t_avrg2, t_min2 = :t_min2, t_max2 = :t_max2, note2 = :note2
            WHERE id = :id
        ';

        $insertStmt = $this->connection->prepare($insertSql);
        $updateStmt1 = $this->connection->prepare($updateSql1);
        $updateStmt2 = $this->connection->prepare($updateSql2);
        $updateStmtBoth = $this->connection->prepare($updateSqlBoth);

        $affectedCount = 0;

        foreach ($records as $record) {
            $operation = $record['operation'] ?? 'insert';
            $existingRecordId = $record['existing_record_id'] ?? null;

            if ($operation === 'update' && $existingRecordId !== null) {
                if ($entry === 1) {
                    // Update only entry 1 fields
                    $updateStmt1->executeStatement([
                        'id' => $existingRecordId,
                        'd1' => $record['d1'],
                        't1' => $record['t1'],
                        'rh1' => $record['rh1'],
                        'mkt1' => $record['mkt1'],
                        't_avrg1' => $record['t_avrg1'],
                        't_min1' => $record['t_min1'],
                        't_max1' => $record['t_max1'],
                        'note1' => $record['note1'],
                    ]);
                } elseif ($entry === 2) {
                    // Update only entry 2 fields
                    $updateStmt2->executeStatement([
                        'id' => $existingRecordId,
                        'd2' => $record['d2'],
                        't2' => $record['t2'],
                        'rh2' => $record['rh2'],
                        'mkt2' => $record['mkt2'],
                        't_avrg2' => $record['t_avrg2'],
                        't_min2' => $record['t_min2'],
                        't_max2' => $record['t_max2'],
                        'note2' => $record['note2'],
                    ]);
                } else {
                    // Update both entries
                    $updateStmtBoth->executeStatement([
                        'id' => $existingRecordId,
                        'server_date' => $record['new_server_date'],
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
                }
            } else {
                // Insert new record
                // For per-entry filling, set the other entry's values to null
                $insertData = [
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
                ];

                // For per-entry insert, set the OTHER entry's values to null
                if ($entry === 1) {
                    // Inserting entry 1, set entry 2 to null
                    $insertData['d2'] = 0;
                    $insertData['t2'] = null;
                    $insertData['rh2'] = null;
                    $insertData['mkt2'] = null;
                    $insertData['t_avrg2'] = null;
                    $insertData['t_min2'] = null;
                    $insertData['t_max2'] = null;
                    $insertData['note2'] = null;
                } elseif ($entry === 2) {
                    // Inserting entry 2, set entry 1 to null
                    $insertData['d1'] = 0;
                    $insertData['t1'] = null;
                    $insertData['rh1'] = null;
                    $insertData['mkt1'] = null;
                    $insertData['t_avrg1'] = null;
                    $insertData['t_min1'] = null;
                    $insertData['t_max1'] = null;
                    $insertData['note1'] = null;
                }

                $insertStmt->executeStatement($insertData);
            }
            $affectedCount++;
        }

        return $affectedCount;
    }
}
