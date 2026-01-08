<?php

namespace App\Service\DeviceData;

use App\Repository\DeviceDataArchiveRepository;
use App\Repository\DeviceDataRepository;
use App\Repository\DeviceRepository;

class ShiftDeviceDataService
{
    public function __construct(
        private readonly DeviceDataRepository $deviceDataRepository,
        private readonly DeviceRepository $deviceRepository,
        private readonly DeviceDataArchiveRepository $deviceDataArchiveRepository,
        private readonly DeviceDataDailyArchiveService $dailyArchiveService
    ) {
    }

    private const MIN_INTERVAL_DAYS = 20;
    private const MAX_INTERVAL_DAYS = 35;

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
            $count = $this->deviceDataRepository->countShiftedDataForInterval(
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

        $records = $this->deviceDataRepository->getShiftedDataPreview(
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
        $this->deviceDataRepository->deleteEmptyRecordsInRange(
            $deviceId,
            $dateFrom,
            $dateTo
        );

        // Insert shifted data
        $insertedCount = $this->deviceDataRepository->insertShiftedData(
            $deviceId,
            $dateFrom,
            $dateTo,
            $intervalDays
        );

        // Create new daily archives (excluding today if it's in the range)
        $this->dailyArchiveService->generateDailyArchivesForDateRange($device, $dateFrom, $dateTo);

        return $insertedCount;
    }
}
