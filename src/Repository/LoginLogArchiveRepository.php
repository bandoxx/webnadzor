<?php

namespace App\Repository;

use App\Entity\LoginLogArchive;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LoginLogArchive>
 *
 * @method LoginLogArchive|null find($id, $lockMode = null, $lockVersion = null)
 * @method LoginLogArchive|null findOneBy(array $criteria, array $orderBy = null)
 * @method LoginLogArchive[]    findAll()
 * @method LoginLogArchive[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LoginLogArchiveRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LoginLogArchive::class);
    }
}
