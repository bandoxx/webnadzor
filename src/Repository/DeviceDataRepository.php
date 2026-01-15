<?php

namespace App\Repository;

use App\Entity\Device;
use App\Entity\DeviceData;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DeviceData>
 *
 * @method DeviceData|null find($id, $lockMode = null, $lockVersion = null)
 * @method DeviceData|null findOneBy(array $criteria, array $orderBy = null)
 * @method DeviceData[]    findAll()
 * @method DeviceData[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DeviceDataRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DeviceData::class);
    }

    public function getLast50Records(int $deviceId): array
    {
        return $this->createQueryBuilder('dd')
            ->where('dd.device = :deviceId')->setParameter('deviceId', $deviceId)
            ->orderBy('dd.deviceDate', 'DESC')
            ->setMaxResults(50)
            ->getQuery()
            ->getResult()
        ;
    }

    public function getNumberOfRecordsForLastDay(int $deviceId)
    {
        return $this->createQueryBuilder('dd')
            ->select('COUNT(dd)')
            ->where('dd.device = :deviceId')->setParameter('deviceId', $deviceId)
            ->andWhere('dd.deviceDate >= :fromDate')->setParameter('fromDate', (new \DateTime('-1 day'))->setTime(0, 0, 0))
            ->andWhere('dd.deviceDate <= :toDate')->setParameter('toDate', (new \DateTime('-1 day'))->setTime(23, 59))
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    public function getFirstRecord(int $deviceId): ?DeviceData
    {
        return $this->createQueryBuilder('dd')
            ->where('dd.device = :device_id')
            ->setParameter('device_id', $deviceId)
            ->orderBy('dd.deviceDate' , 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function removeDataForDevice(int $deviceId): void
    {
        $this->getEntityManager()->getConnection()->executeStatement(
            "DELETE FROM device_data WHERE device_id = :deviceId",
            ['deviceId' => $deviceId]
        );
    }

    public function getNumberOfRecords(int $deviceId, ?\DateTime $fromDate = null, ?\DateTime $toDate = null): int
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

    public function getDateDifferenceBetweenFirstAndLastRecord(int $deviceId): int
    {
        return abs($this->createQueryBuilder('d')
            ->select('DATEDIFF(MIN(d.deviceDate), MAX(d.deviceDate))')
            ->where('d.device = :device')->setParameter('device', $deviceId)
            ->getQuery()
            ->getSingleScalarResult())
        ;
    }

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

    public function findLastRecordForDevice(Device $device): ?DeviceData
    {
        return $this->createQueryBuilder('dd')
            ->where('dd.device = :device_id')
            ->setParameter('device_id', $device->getId())
            ->orderBy('dd.deviceDate', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /**
     * Fetch last records for multiple devices in a single query.
     *
     * @param Device[] $devices
     * @return array<int, DeviceData> [deviceId => lastRecord]
     */
    public function findLastRecordsByDevices(array $devices): array
    {
        if (empty($devices)) {
            return [];
        }

        $deviceIds = array_map(fn(Device $d) => $d->getId(), $devices);

        // Use a subquery to get the max deviceDate per device, then fetch those records
        $subQuery = $this->createQueryBuilder('sub')
            ->select('MAX(sub.id)')
            ->where('sub.device IN (:deviceIds)')
            ->groupBy('sub.device')
            ->getDQL();

        $records = $this->createQueryBuilder('dd')
            ->where("dd.id IN ($subQuery)")
            ->setParameter('deviceIds', $deviceIds)
            ->getQuery()
            ->getResult();

        $indexed = [];
        /** @var DeviceData $record */
        foreach ($records as $record) {
            $deviceId = $record->getDevice()->getId();
            $indexed[$deviceId] = $record;
        }

        return $indexed;
    }

    public function findLastRecordForDeviceAndEntry(Device $device, $entry): ?DeviceData
    {
        // Try cache first
        try {
            $em = $this->getEntityManager();
            $cacheRepo = $em->getRepository(\App\Entity\DeviceDataLastCache::class);
            $cache = $cacheRepo->findOneBy(['device' => $device, 'entry' => (int)$entry]);
            if ($cache) {
                return $cache->getDeviceData();
            }
        } catch (\Throwable $e) {
            // Fallback silently to live query if cache entity is missing or not migrated
        }

        return $this->createQueryBuilder('dd')
            ->where('dd.device = :device_id')
            ->andWhere("dd.t$entry IS NOT NULL")
            ->setParameter('device_id', $device->getId())
            ->orderBy('dd.deviceDate', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findLastRecordForDeviceId(int $deviceId, int $entry): ?DeviceData
    {
        return $this->createQueryBuilder('dd')
            ->where('dd.device = :device_id')
            ->andWhere("dd.t$entry IS NOT NULL")
            ->setParameter('device_id', $deviceId)
            ->orderBy('dd.deviceDate', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findByDeviceAndForDay(Device $device, \DateTime $dateTime): array
    {
        $start = (clone ($dateTime))->setTime(0, 0);
        $end = (clone ($dateTime))->setTime(23, 59);

        return $this->createQueryBuilder('dd')
            ->where('dd.device = :device_id')
            ->andWhere('dd.deviceDate >= :start AND dd.deviceDate <= :end')
            ->setParameter('device_id', $device->getId())
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->orderBy('dd.deviceDate', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findByDeviceAndBetweenDates(Device $device, \DateTime $fromDate, \DateTime $toDate): array
    {
        $fromDate->setTime(0, 0);
        $toDate->setTime(23, 59);

        return $this->createQueryBuilder('dd')
            ->where('dd.device = :device_id')
            ->andWhere('dd.deviceDate >= :start AND dd.deviceDate <= :end')
            ->setParameter('device_id', $device->getId())
            ->setParameter('start', $fromDate)
            ->setParameter('end', $toDate)
            ->orderBy('dd.deviceDate', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findByDeviceAndForMonth(Device $device, \DateTime $dateTime): array
    {
        $start = (clone ($dateTime))->modify('first day of this month')->setTime(0, 0);
        $end = (clone ($dateTime))->modify('last day of this month')->setTime(23, 59);

        return $this->createQueryBuilder('dd')
            ->where('dd.device = :device_id')
            ->andWhere('dd.deviceDate >= :start AND dd.deviceDate <= :end')
            ->setParameter('device_id', $device->getId())
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->orderBy('dd.deviceDate', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }
}
