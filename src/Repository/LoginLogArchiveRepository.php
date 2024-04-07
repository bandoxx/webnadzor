<?php

namespace App\Repository;

use App\Entity\LoginLogArchive;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LoginLogArchive>
 *
 * @method LoginLogArchive|null find($id, $lockMode = null, $lockVersion = null)
 * @method LoginLogArchive|null findOneBy(array $criteria, array $orderBy = null)
 * @method LoginLogArchive[]    findAll()
 * @method LoginLogArchive[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LoginLogArchiveRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LoginLogArchive::class);
    }

//    /**
//     * @return LoginLogArchive[] Returns an array of LoginLogArchive objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('l')
//            ->andWhere('l.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('l.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?LoginLogArchive
//    {
//        return $this->createQueryBuilder('l')
//            ->andWhere('l.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
