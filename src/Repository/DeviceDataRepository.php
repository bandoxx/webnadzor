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

    /**
     * @return array<int, DeviceData>
     */
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

    public function getNumberOfRecordsForLastDay(int $deviceId): int
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

    /**
     * @param Device $device
     * @param int $entry
     * @return DeviceData|null
     */
    public function findLastRecordForDeviceAndEntry(Device $device, int $entry): ?DeviceData
    {
        // Try cache first
        try {
            $em = $this->getEntityManager();
            $cacheRepo = $em->getRepository(\App\Entity\DeviceDataLastCache::class);
            $cache = $cacheRepo->findOneBy(['device' => $device, 'entry' => $entry]);
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

    /**
     * @return array<int, DeviceData>
     */
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

    /**
     * @return array<int, DeviceData>
     */
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

    /**
     * @return array<int, DeviceData>
     */
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
