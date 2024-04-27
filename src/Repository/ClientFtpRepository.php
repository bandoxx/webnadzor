<?php

namespace App\Repository;

use App\Entity\ClientFtp;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ClientFtp>
 *
 * @method ClientFtp|null find($id, $lockMode = null, $lockVersion = null)
 * @method ClientFtp|null findOneBy(array $criteria, array $orderBy = null)
 * @method ClientFtp[]    findAll()
 * @method ClientFtp[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ClientFtpRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ClientFtp::class);
    }
}
