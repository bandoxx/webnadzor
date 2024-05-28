<?php

namespace App\Repository;

use App\Entity\Smtp;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Smtp>
 *
 * @method Smtp|null find($id, $lockMode = null, $lockVersion = null)
 * @method Smtp|null findOneBy(array $criteria, array $orderBy = null)
 * @method Smtp[]    findAll()
 * @method Smtp[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SmtpRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Smtp::class);
    }

    //    /**
    //     * @return Smtp[] Returns an array of Smtp objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('s.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Smtp
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
