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

    public function getFirstRecord(int $deviceId): ?DeviceData
    {
        return $this->createQueryBuilder('dd')
            ->where('dd.id = :id')
            ->setParameter('id', $deviceId)
            ->orderBy('dd.deviceDate' , 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function getLastRecord(int $deviceId): ?DeviceData
    {
        return $this->createQueryBuilder('dd')
            ->where('dd.id = :id')
            ->setParameter('id', $deviceId)
            ->orderBy('dd.deviceDate' , 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function removeDataForDevice(int $deviceId): void
    {
        $this->getEntityManager()->getConnection()->executeQuery(
            "DELETE FROM device_data WHERE device_id = $deviceId",
        )->free();
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

    public function getChartData(int $deviceId, ?\DateTime $fromDate = null, ?\DateTime $toDate = null): array
    {
        $numberOfRecords = $this->getNumberOfRecords($deviceId, $fromDate, $toDate);

        $builder = $this->createQueryBuilder('dd')
            ->select("DATE(dd.deviceDate) as date", "YEAR(dd.deviceDate) as year", "HOUR(dd.deviceDate) as hour", "WEEK(dd.deviceDate) as week", "dd")
            ->where("dd.device = :device_id")
            ->setParameter('device_id', $deviceId)
        ;

        if ($fromDate && $toDate) {
            $builder->andWhere('dd.deviceDate >= :from_date')
                ->andWhere('dd.deviceDate <= :to_date')
                ->setParameter('from_date', $fromDate)
                ->setParameter('to_date', $toDate)
            ;

            $daysDiff = $toDate->diff($fromDate)->format("%a");
        } else {
            $daysDiff = $this->getDateDifferenceBetweenFirstAndLastRecord($deviceId);
        }

        if ($numberOfRecords > 288 && $daysDiff > 2) {
            if ($daysDiff < 15) {
                $builder->groupBy('date', 'hour');
            } elseif ($daysDiff < 365 * 2) {
                $builder->groupBy('date');
            } else {
                $builder->groupBy('year', 'week');
            }
        }

        return $builder
            ->orderBy('dd.deviceDate', 'ASC')
            ->getQuery()
            ->getResult()
        ;
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

    public function findLastRecordForDeviceAndEntry(Device $device, $entry): ?DeviceData
    {
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
            ->setParameter('device_id', $device->getid())
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->orderBy('dd.deviceDate', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findByDeviceAndBetweenDates(Device $device, \DateTime $fromDate, \DateTime $toDate): array
    {
        return $this->createQueryBuilder('dd')
            ->where('dd.device = :device_id')
            ->andWhere('dd.deviceDate >= :start AND dd.deviceDate <= :end')
            ->setParameter('device_id', $device->getid())
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
            ->setParameter('device_id', $device->getid())
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->orderBy('dd.deviceDate', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }
}
