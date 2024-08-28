<?php

namespace App\Repository;


use App\Entity\ClientStorageDevice;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ClientStorageDevice>
 *
 * @method ClientStorageDevice|null find($id, $lockMode = null, $lockVersion = null)
 * @method ClientStorageDevice|null findOneBy(array $criteria, array $orderBy = null)
 * @method ClientStorageDevice[]    findAll()
 * @method ClientStorageDevice[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ClientStorageDeviceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ClientStorageDevice::class);
    }

    /**
     * @return ClientStorageDevice[]
     */
    public function findAllActive(): array
    {
        return $this->findBy(['isDeleted' => false]);
    }

    public function getByClientId($clientId)
    {
        $qb = $this->createQueryBuilder('d')
            ->where('d.client_storage = :clientId')
            ->setParameter('clientId', $clientId);

        return $qb->getQuery()->getScalarResult();
    }

    public function deleteByClientStorageId($clientStorageId)
    {
        $qb = $this->createQueryBuilder('d')
            ->delete()
            ->where('d.client_storage = :clientStorageId')
            ->setParameter('clientStorageId', $clientStorageId);

        return $qb->getQuery()->execute();
    }

    public function findById(int $id): ?ClientStorageDevice
    {
        return $this->find($id);
    }
}
