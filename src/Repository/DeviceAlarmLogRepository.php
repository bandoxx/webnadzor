<?php

namespace App\Repository;

use App\Entity\DeviceAlarmLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DeviceAlarmLog>
 */
class DeviceAlarmLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DeviceAlarmLog::class);
    }

    public function findByDates(\DateTime $dateFrom, \DateTime $dateTo)
    {
        $dateFrom->setTime(0, 0, 0);
        $dateTo->setTime(23, 59, 59);

        return $this->createQueryBuilder('a')
            ->where('a.createdAt >= :dateFrom')
            ->setParameter('dateFrom', $dateFrom)
            ->andWhere('a.createdAt <= :dateTo')
            ->setParameter('dateTo', $dateTo)
            ->getQuery()
            ->getResult()
        ;
    }
}
