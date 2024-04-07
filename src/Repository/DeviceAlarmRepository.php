<?php

namespace App\Repository;

use App\Entity\Device;
use App\Entity\DeviceAlarm;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DeviceAlarm>
 *
 * @method DeviceAlarm|null find($id, $lockMode = null, $lockVersion = null)
 * @method DeviceAlarm|null findOneBy(array $criteria, array $orderBy = null)
 * @method DeviceAlarm[]    findAll()
 * @method DeviceAlarm[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DeviceAlarmRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DeviceAlarm::class);
    }

    public function findByDevice(Device $device): array
    {
        return $this->findBy(['device' => $device], ['deviceDate' => 'DESC']);
    }

//    /**
//     * @return DeviceAlarm[] Returns an array of DeviceAlarm objects
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

//    public function findOneBySomeField($value): ?DeviceAlarm
//    {
//        return $this->createQueryBuilder('d')
//            ->andWhere('d.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
