<?php

namespace App\Repository;

use App\Entity\DeviceIcon;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DeviceIcon>
 *
 * @method DeviceIcon|null find($id, $lockMode = null, $lockVersion = null)
 * @method DeviceIcon|null findOneBy(array $criteria, array $orderBy = null)
 * @method DeviceIcon[]    findAll()
 * @method DeviceIcon[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DeviceIconRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DeviceIcon::class);
    }

//    /**
//     * @return DeviceIcon[] Returns an array of DeviceIcon objects
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

//    public function findOneBySomeField($value): ?DeviceIcon
//    {
//        return $this->createQueryBuilder('d')
//            ->andWhere('d.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
