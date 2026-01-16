<?php

namespace App\Repository;

use App\Entity\SmsDeliveryReport;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SmsDeliveryReport>
 */
class SmsDeliveryReportRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SmsDeliveryReport::class);
    }

    /**
     * @return array<SmsDeliveryReport>
     */
    public function findByDates(?\DateTime $dateFrom = null, ?\DateTime $dateTo = null): array
    {
        $queryBuilder = $this->createQueryBuilder('a');

        if ($dateFrom) {
            $dateFrom->setTime(0, 0, 0);

            $queryBuilder->andWhere('a.createdAt >= :dateFrom')
                ->setParameter('dateFrom', $dateFrom);
        }

        if ($dateFrom && $dateTo) {
            $dateTo->setTime(23, 59, 59);

            $queryBuilder->andWhere('a.createdAt <= :dateTo')
                ->setParameter('dateTo', $dateTo);
        }

        return $queryBuilder
            ->orderBy('a.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
