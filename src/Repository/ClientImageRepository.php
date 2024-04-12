<?php

namespace App\Repository;

use App\Entity\ClientImage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ClientImage>
 *
 * @method ClientImage|null find($id, $lockMode = null, $lockVersion = null)
 * @method ClientImage|null findOneBy(array $criteria, array $orderBy = null)
 * @method ClientImage[]    findAll()
 * @method ClientImage[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ClientImageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ClientImage::class);
    }

//    /**
//     * @return ClientImage[] Returns an array of ClientImage objects
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

//    public function findOneBySomeField($value): ?ClientImage
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
