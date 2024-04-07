<?php

namespace App\Repository;

use App\Entity\DeviceDataEntry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DeviceDataEntry>
 *
 * @method DeviceDataEntry|null find($id, $lockMode = null, $lockVersion = null)
 * @method DeviceDataEntry|null findOneBy(array $criteria, array $orderBy = null)
 * @method DeviceDataEntry[]    findAll()
 * @method DeviceDataEntry[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DeviceDataEntryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DeviceDataEntry::class);
    }

//    /**
//     * @return DeviceDataEntry[] Returns an array of DeviceDataEntry objects
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

//    public function findOneBySomeField($value): ?DeviceDataEntry
//    {
//        return $this->createQueryBuilder('d')
//            ->andWhere('d.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
