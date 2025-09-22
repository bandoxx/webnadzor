<?php

namespace App\Repository;

use App\Entity\Client;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Client>
 *
 * @method Client|null find($id, $lockMode = null, $lockVersion = null)
 * @method Client|null findOneBy(array $criteria, array $orderBy = null)
 * @method Client[]    findAll()
 * @method Client[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ClientRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Client::class);
    }

    /**
     * @return Client[]
     */
    public function findAllActive(): array
    {
        return $this->findBy(['isDeleted' => false]);
    }

    /**
     * Find active (not deleted) clients by a list of IDs
     *
     * @param int[] $ids
     * @return Client[]
     */
    public function findActiveByIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        return $this->createQueryBuilder('c')
            ->where('c.id IN (:ids)')
            ->andWhere('c.isDeleted = :isDeleted')
            ->setParameter('ids', $ids)
            ->setParameter('isDeleted', false)
            ->getQuery()
            ->getResult();
    }
}
