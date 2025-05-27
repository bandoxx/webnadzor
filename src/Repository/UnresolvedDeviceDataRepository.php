<?php

namespace App\Repository;

use App\Entity\UnresolvedDeviceData;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UnresolvedDeviceData>
 */
class UnresolvedDeviceDataRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UnresolvedDeviceData::class);
    }

    public function findWithoutContent(): array
    {
        return $this->createQueryBuilder('u')
            ->select('u.id, u.xmlName, u.createdAt')
            ->orderBy('u.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findOlderThen(int $days)
    {
        return $this->createQueryBuilder('u')
            ->where('u.createdAt <= :date')
            ->setParameter('date', new \DateTime("-$days days"))
            ->getQuery()
            ->getResult()
        ;
    }
}
