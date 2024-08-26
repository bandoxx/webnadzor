<?php

namespace App\Repository;


use App\Entity\ClientStorage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ClientStorage>
 *
 * @method ClientStorage|null find($id, $lockMode = null, $lockVersion = null)
 * @method ClientStorage|null findOneBy(array $criteria, array $orderBy = null)
 * @method ClientStorage[]    findAll()
 * @method ClientStorage[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ClientStorageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ClientStorage::class);
    }


    public function findIdsByClientId(int $clientId): array
    {
        $qb = $this->createQueryBuilder('c')
            ->select('c.id')
            ->where('c.client = :clientId')
            ->setParameter('clientId', $clientId);

        return $qb->getQuery()->getSingleColumnResult();
    }
}
