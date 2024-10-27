<?php

namespace App\Repository;

use App\Entity\Client;
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

    /**
     * @return array<DeviceAlarmLog>
     */
    public function findByDates(Client $client, ?\DateTime $dateFrom = null, ?\DateTime $dateTo = null): array
    {
        $queryBuilder = $this->createQueryBuilder('a')
            ->where('a.client = :client')
            ->setParameter('client', $client)
        ;

        if ($dateFrom && $dateTo) {
            $dateFrom->setTime(0, 0, 0);
            $dateTo->setTime(23, 59, 59);

            $queryBuilder->andWhere('a.createdAt >= :dateFrom')
                ->setParameter('dateFrom', $dateFrom)
                ->andWhere('a.createdAt <= :dateTo')
                ->setParameter('dateTo', $dateTo)
            ;
        }

        return $queryBuilder
            ->getQuery()
            ->getResult()
        ;
    }
}
