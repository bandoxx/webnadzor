<?php

namespace App\Repository;

use App\Entity\ClientSetting;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ClientSetting>
 *
 * @method ClientSetting|null find($id, $lockMode = null, $lockVersion = null)
 * @method ClientSetting|null findOneBy(array $criteria, array $orderBy = null)
 * @method ClientSetting[]    findAll()
 * @method ClientSetting[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ClientSettingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ClientSetting::class);
    }
}
