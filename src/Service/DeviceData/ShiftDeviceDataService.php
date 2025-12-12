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

    /**
     * Preview device data that would be inserted with shifted dates
     *
     * @param int $deviceId
     * @param \DateTimeInterface $dateFrom
     * @param \DateTimeInterface $dateTo
     * @param int $intervalDays Number of days to shift (default 25)
     * @return array Array of associative arrays with old and new dates plus all data
     */
    public function previewShiftedData(
        int $deviceId,
        \DateTimeInterface $dateFrom,
        \DateTimeInterface $dateTo,
        int $intervalDays = 25
    ): array {
        return $this->deviceDataRepository->getShiftedDataPreview(
            $deviceId,
            $dateFrom,
            $dateTo,
            $intervalDays
        );
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
