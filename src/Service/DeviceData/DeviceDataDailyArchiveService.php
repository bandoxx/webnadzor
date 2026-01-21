<?php

namespace App\Service\DeviceData;

use App\Entity\Device;
use App\Entity\DeviceDataArchive;
use App\Factory\DeviceDataArchiveFactory;
use App\Repository\DeviceDataRepository;
use App\Service\Archiver\ArchiverInterface;
use App\Service\Archiver\DeviceData\DeviceDataPDFArchiver;
use App\Service\Archiver\DeviceData\DeviceDataXLSXArchiver;
use App\Service\Chart\ChartImageGenerator;
use App\Service\RawData\Factory\DeviceDataRawDataFactory;
use App\Service\RawData\RawDataHandler;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Service for creating daily device data archives
 */
class DeviceDataDailyArchiveService
{
    public function __construct(
        private readonly DeviceDataXLSXArchiver $XLSXArchiver,
        private readonly DeviceDataPDFArchiver $PDFArchiver,
        private readonly DeviceDataRepository $deviceDataRepository,
        private readonly DeviceDataArchiveFactory $deviceDataArchiveFactory,
        private readonly RawDataHandler $rawDataHandler,
        private readonly DeviceDataRawDataFactory $deviceDataRawDataFactory,
        private readonly ChartImageGenerator $chartImageGenerator,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Generate daily report for a device, entry, and date
     *
     * @param Device $device
     * @param array $data Device data for the day
     * @param int $entry Entry number (1 or 2)
     * @param \DateTime $date Archive date
     * @param bool $flushImmediately Whether to flush to database immediately
     * @return void
     */
    public function generateDailyReport(
        Device $device,
        array $data,
        int $entry,
        \DateTime $date,
        bool $flushImmediately = true
    ): void {
        $fileName = $this->generateFilename(
            sprintf('d%s_%s', $device->getId(), $device->getDeviceIdentifier()),
            $entry,
            $date->format(ArchiverInterface::DAILY_FILENAME_FORMAT)
        );

        $this->XLSXArchiver->saveDaily($device, $data, $entry, $date, $fileName);
        $archive = $this->PDFArchiver->saveDaily($device, $data, $entry, $date, $fileName);

        $this->rawDataHandler->encrypt(
            $this->deviceDataRawDataFactory->create($data, $entry, $date),
            $archive->getFullPathWithoutExtension()
        );

        $archiveEntity = $this->deviceDataArchiveFactory->create(
            $device,
            $date,
            $entry,
            $fileName,
            DeviceDataArchive::PERIOD_DAY
        );

        $this->entityManager->persist($archiveEntity);

        // Only flush immediately if requested (for backward compatibility)
        if ($flushImmediately) {
            $this->entityManager->flush();
        }
    }

    /**
     * Generate daily archives for a device within a date range
     * Excludes today's date as it will be handled by cron
     *
     * @param Device $device
     * @param \DateTimeInterface $dateFrom
     * @param \DateTimeInterface $dateTo
     * @param int|null $entry Entry number (1 or 2) to generate archives for, null for both entries
     * @return int Number of archives created
     */
    public function generateDailyArchivesForDateRange(
        Device $device,
        \DateTimeInterface $dateFrom,
        \DateTimeInterface $dateTo,
        ?int $entry = null
    ): int {
        $archiveCount = 0;
        $batchSize = 20;

        // Adjust toDate to exclude today if it's in the range
        $adjustedToDate = $this->adjustToDateExcludingToday($dateTo);

        // If adjustedToDate is before dateFrom, no archives to create
        if ($adjustedToDate < $dateFrom) {
            return 0;
        }

        // Get all dates in the range (excluding today)
        $dates = $this->getDatesInRange($dateFrom, $adjustedToDate);

        // Determine which entries to process
        $entries = $entry !== null ? [$entry] : Device::SENSOR_ENTRIES;

        foreach ($dates as $date) {
            // Get device data for this day
            $data = $this->deviceDataRepository->findByDeviceAndForDay($device, $date);

            // Create archives for specified entry/entries
            foreach ($entries as $entryNum) {
                $fromDate = (clone $date)->setTime(0, 0, 0);
                $toDate = (clone $date)->setTime(23, 59, 59);

                // Generate chart image
                $this->chartImageGenerator->generateTemperatureAndHumidityChartImage(
                    $device,
                    $entryNum,
                    $fromDate,
                    $toDate
                );

                // Generate daily report (without immediate flush)
                $this->generateDailyReport($device, $data, $entryNum, $date, false);

                $archiveCount++;

                // Flush every $batchSize archives
                if ($archiveCount % $batchSize === 0) {
                    $this->entityManager->flush();
                }
            }

            // Detach DeviceData entities to free memory
            foreach ($data as $row) {
                $this->entityManager->detach($row);
            }

            unset($data);
            gc_collect_cycles();
        }

        // Final flush for any remaining archives
        if ($archiveCount % $batchSize !== 0) {
            $this->entityManager->flush();
        }

        return $archiveCount;
    }

    /**
     * Adjust toDate to exclude today
     * If toDate is today or in the future, return yesterday
     * Otherwise return the original toDate
     *
     * @param \DateTimeInterface $dateTo
     * @return \DateTime
     */
    private function adjustToDateExcludingToday(\DateTimeInterface $dateTo): \DateTime
    {
        $today = (new \DateTime())->setTime(0, 0, 0);
        $adjustedDate = (clone $dateTo)->setTime(0, 0, 0);

        // If the date is today or later, return yesterday
        if ($adjustedDate >= $today) {
            return (new \DateTime('-1 day'))->setTime(0, 0, 0);
        }

        return $adjustedDate;
    }

    /**
     * Get all dates in a date range as an array
     *
     * @param \DateTimeInterface $dateFrom
     * @param \DateTimeInterface $dateTo
     * @return array<\DateTime>
     */
    private function getDatesInRange(\DateTimeInterface $dateFrom, \DateTimeInterface $dateTo): array
    {
        $from = (clone $dateFrom)->setTime(0, 0, 0);
        $to = (clone $dateTo)->setTime(0, 0, 0);

        $period = new \DatePeriod(
            $from,
            new \DateInterval('P1D'),
            $to->modify('+1 day') // Include end date
        );

        return iterator_to_array($period);
    }

    /**
     * Generate archive filename
     *
     * @param string $identifier
     * @param int $entry
     * @param string $date
     * @return string
     */
    private function generateFilename(string $identifier, int $entry, string $date): string
    {
        $text = sprintf('%s_t%s_%s', $identifier, $entry, $date);
        $text = preg_replace('/[^a-zA-Z0-9]+/u', '_', $text);

        // Trim and lowercase
        $text = trim($text, '-');
        return mb_strtolower($text, 'UTF-8');
    }
}
