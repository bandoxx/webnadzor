<?php

namespace App\Repository;

use App\Entity\ClientFtp;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ClientFtp>
 *
 * @method ClientFtp|null find($id, $lockMode = null, $lockVersion = null)
 * @method ClientFtp|null findOneBy(array $criteria, array $orderBy = null)
 * @method ClientFtp[]    findAll()
 * @method ClientFtp[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ClientFtpRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ClientFtp::class);
    }

//    /**
//     * @return ClientInfo[] Returns an array of ClientInfo objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('c.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?ClientInfo
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
