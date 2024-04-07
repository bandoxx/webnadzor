<?php

namespace App\Repository;

use App\Entity\DeviceAlarmConfig;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DeviceAlarmConfig>
 *
 * @method DeviceAlarmConfig|null find($id, $lockMode = null, $lockVersion = null)
 * @method DeviceAlarmConfig|null findOneBy(array $criteria, array $orderBy = null)
 * @method DeviceAlarmConfig[]    findAll()
 * @method DeviceAlarmConfig[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DeviceAlarmConfigRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DeviceAlarmConfig::class);
    }

//    /**
//     * @return DeviceAlarmConfig[] Returns an array of DeviceAlarmConfig objects
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

//    public function findOneBySomeField($value): ?DeviceAlarmConfig
//    {
//        return $this->createQueryBuilder('d')
//            ->andWhere('d.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
