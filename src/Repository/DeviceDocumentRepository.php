<?php

namespace App\Repository;

use App\Entity\DeviceDocument;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DeviceDocument>
 *
 * @method DeviceDocument|null find($id, $lockMode = null, $lockVersion = null)
 * @method DeviceDocument|null findOneBy(array $criteria, array $orderBy = null)
 * @method DeviceDocument[]    findAll()
 * @method DeviceDocument[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DeviceDocumentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DeviceDocument::class);
    }

    //    /**
    //     * @return DeviceDocument[] Returns an array of DeviceDocument objects
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

    //    public function findOneBySomeField($value): ?DeviceDocument
    //    {
    //        return $this->createQueryBuilder('d')
    //            ->andWhere('d.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
