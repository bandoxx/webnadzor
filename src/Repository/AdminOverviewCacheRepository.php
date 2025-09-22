<?php

namespace App\Repository;

use App\Entity\AdminOverviewCache;
use App\Entity\Client;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AdminOverviewCache>
 *
 * @method AdminOverviewCache|null find($id, $lockMode = null, $lockVersion = null)
 * @method AdminOverviewCache|null findOneBy(array $criteria, array $orderBy = null)
 * @method AdminOverviewCache[]    findAll()
 * @method AdminOverviewCache[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AdminOverviewCacheRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AdminOverviewCache::class);
    }

    public function findOneByClient(Client $client): ?AdminOverviewCache
    {
        return $this->findOneBy(['client' => $client]);
    }
}
