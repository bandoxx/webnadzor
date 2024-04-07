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

    public function getChartData($deviceId, ?\DateTime $fromDate = null, ?\DateTime $toDate = null)
    {
        ## zadnji datum ubacit u js od grafova
        // 2 days or 288 points - all data
        //if ($this->countData() > 288 AND $range > 2 * 24 * 3600) {
        //
        //    // 15 days - hourly data
        //    if ($range < 15 * 24 * 3600) {
        //        $groupby = 'GROUP BY DATE(device_date), HOUR(device_date)';
        //    }
        //
        //    // ~two years - daily data
        //    elseif ($range < 2 * 355 * 24 * 3600) {
        //        $groupby = 'GROUP BY DATE(device_date)';
        //    }
        //
        //    // more than two years - weekly data
        //    else {
        //        $groupby = 'GROUP BY YEAR(device_date), WEEK(device_date)';
        //    }
        //
        //}
        //else {
        //    $groupby = '';
        //}

        $builder = $this->createQueryBuilder('dd')
            //->select("DATE(dd.deviceDate) as d_date, HOUR(dd.deviceDate) as hour", "dd.deviceDate", "dd.entry1", "dd.entry2")
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

            if ($daysDiff < 15) {
                $builder->groupBy('date', 'hour');
            } else if ($daysDiff < 365 * 2) {
                $builder->groupBy('date');
            } else {
                $builder->groupBy('year', 'week');
            }
        } else {
            $builder->groupBy('date');
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

    public function findLastRecordForDeviceId($deviceId, $entry): ?DeviceData
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

    public function findByDeviceAndForDay(Device $device, \DateTime $dateTime)
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

    public function findByDeviceAndBetweenDates(Device $device, \DateTime $fromDate, \DateTime $toDate)
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

    public function findByDeviceAndForMonth(Device $device, \DateTime $dateTime)
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

    public function getMaxRangeForDevice(Device $device)
    {
        return $this->getEntityManager()
            ->getConnection()
            ->executeQuery(sprintf("SELECT TIMESTAMPDIFF(SECOND, MIN(device_date), MAX(device_date)) as max_range FROM device_data WHERE device_id = %d", $device->getId()))
            ->fetchOne();
    }

//    /**
//     * @return DeviceData[] Returns an array of DeviceData objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('d')
//            ->andWhere('d.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('d.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?DeviceData
//    {
//        return $this->createQueryBuilder('d')
//            ->andWhere('d.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
