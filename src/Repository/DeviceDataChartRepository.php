<?php

namespace App\Repository;

use App\Entity\DeviceData;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository for chart-related DeviceData queries.
 * Handles data fetching optimized for chart rendering with sampling for large datasets.
 *
 * @extends ServiceEntityRepository<DeviceData>
 */
class DeviceDataChartRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DeviceData::class);
    }

    /**
     * Get device chart data with a simple limit (for device-level charts like battery/signal).
     *
     * @param int $deviceId
     * @param int $limit
     * @return array<int, DeviceData>
     */
    public function getDeviceChartData(int $deviceId, int $limit = 20): array
    {
        return $this->createQueryBuilder('dd')
            ->where('dd.device = :device')->setParameter('device', $deviceId)
            ->orderBy('dd.deviceDate', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Get chart data with intelligent sampling for large datasets.
     * Automatically samples data based on time span to prevent performance issues.
     *
     * @param int $deviceId
     * @param \DateTime|null $fromDate
     * @param \DateTime|null $toDate
     * @return array<int, array<string, mixed>>
     */
    public function getChartData(int $deviceId, ?\DateTime $fromDate = null, ?\DateTime $toDate = null): array
    {
        $numberOfRecords = $this->getNumberOfRecords($deviceId, $fromDate, $toDate);

        if ($fromDate && $toDate) {
            $daysDiff = (int) $toDate->diff($fromDate)->format("%a");
        } else {
            $daysDiff = $this->getDateDifferenceBetweenFirstAndLastRecord($deviceId);
        }

        // If few records or short time span, return all data
        if ($numberOfRecords <= 288 || $daysDiff <= 2) {
            return $this->getChartDataAll($deviceId, $fromDate, $toDate);
        }

        // Calculate target points based on time span
        $targetPoints = $this->calculateTargetPoints($daysDiff);
        $step = max(1, (int) floor($numberOfRecords / $targetPoints));

        return $this->getChartDataSampled($deviceId, $fromDate, $toDate, $step);
    }

    /**
     * Count total records for a device within optional date range.
     *
     * @param int $deviceId
     * @param \DateTime|null $fromDate
     * @param \DateTime|null $toDate
     * @return int
     */
    private function getNumberOfRecords(int $deviceId, ?\DateTime $fromDate = null, ?\DateTime $toDate = null): int
    {
        $builder = $this->createQueryBuilder('dd');
        $builder->select('COUNT(dd.id) as count');
        if ($fromDate && $toDate) {
            $builder->where('dd.deviceDate BETWEEN :fromDate AND :toDate')
                ->setParameter('fromDate', $fromDate)
                ->setParameter('toDate', $toDate)
            ;
        }

        return $builder->andWhere('dd.device = :device_id')
            ->setParameter('device_id', $deviceId)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    /**
     * Get the number of days between first and last record for a device.
     *
     * @param int $deviceId
     * @return int
     */
    private function getDateDifferenceBetweenFirstAndLastRecord(int $deviceId): int
    {
        return abs($this->createQueryBuilder('d')
            ->select('DATEDIFF(MIN(d.deviceDate), MAX(d.deviceDate))')
            ->where('d.device = :device')->setParameter('device', $deviceId)
            ->getQuery()
            ->getSingleScalarResult())
        ;
    }

    /**
     * Calculate optimal number of data points based on time span.
     *
     * @param int $daysDiff
     * @return int
     */
    private function calculateTargetPoints(int $daysDiff): int
    {
        if ($daysDiff < 15) {
            return 288; // ~24 points per day
        } elseif ($daysDiff < 365 * 2) {
            return min($daysDiff, 730); // ~1 point per day, max 730
        } else {
            return 365; // ~1 point per 2 days for very long spans
        }
    }

    /**
     * Get all chart data without sampling (for small datasets).
     *
     * @param int $deviceId
     * @param \DateTime|null $fromDate
     * @param \DateTime|null $toDate
     * @return array<int, array<string, mixed>>
     */
    private function getChartDataAll(int $deviceId, ?\DateTime $fromDate, ?\DateTime $toDate): array
    {
        $builder = $this->createQueryBuilder('dd')
            ->select("DATE(dd.deviceDate) as date", "YEAR(dd.deviceDate) as year", "HOUR(dd.deviceDate) as hour", "WEEK(dd.deviceDate) as week", "dd")
            ->where("dd.device = :device_id")
            ->setParameter('device_id', $deviceId);

        if ($fromDate && $toDate) {
            $builder->andWhere('dd.deviceDate >= :from_date')
                ->andWhere('dd.deviceDate <= :to_date')
                ->setParameter('from_date', $fromDate)
                ->setParameter('to_date', $toDate);
        }

        return $builder
            ->orderBy('dd.deviceDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get sampled chart data (for large datasets).
     * Samples every Nth record while always including first and last.
     *
     * @param int $deviceId
     * @param \DateTime|null $fromDate
     * @param \DateTime|null $toDate
     * @param int $step
     * @return array<int, array<string, mixed>>
     */
    private function getChartDataSampled(int $deviceId, ?\DateTime $fromDate, ?\DateTime $toDate, int $step): array
    {
        // First, fetch all IDs in order (lightweight query)
        $builder = $this->createQueryBuilder('dd')
            ->select('dd.id')
            ->where('dd.device = :device_id')
            ->setParameter('device_id', $deviceId)
            ->orderBy('dd.deviceDate', 'ASC');

        if ($fromDate && $toDate) {
            $builder->andWhere('dd.deviceDate >= :from_date')
                ->andWhere('dd.deviceDate <= :to_date')
                ->setParameter('from_date', $fromDate)
                ->setParameter('to_date', $toDate);
        }

        $allIds = array_column($builder->getQuery()->getArrayResult(), 'id');

        if (empty($allIds)) {
            return [];
        }

        // Sample IDs: always include first, every Nth, and last
        $sampledIds = [];
        $total = count($allIds);

        for ($i = 0; $i < $total; $i += $step) {
            $sampledIds[] = $allIds[$i];
        }

        // Always include the last record
        $lastId = $allIds[$total - 1];
        if (!in_array($lastId, $sampledIds, true)) {
            $sampledIds[] = $lastId;
        }

        // Fetch full entities for sampled IDs
        return $this->createQueryBuilder('dd')
            ->select("DATE(dd.deviceDate) as date", "YEAR(dd.deviceDate) as year", "HOUR(dd.deviceDate) as hour", "WEEK(dd.deviceDate) as week", "dd")
            ->where('dd.id IN (:ids)')
            ->setParameter('ids', $sampledIds)
            ->orderBy('dd.deviceDate', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
