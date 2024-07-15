<?php

namespace App\Repository;


use App\Entity\ClientStorageText;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ClientStorageText>
 *
 * @method ClientStorageText|null find($id, $lockMode = null, $lockVersion = null)
 * @method ClientStorageText|null findOneBy(array $criteria, array $orderBy = null)
 * @method ClientStorageText[]    findAll()
 * @method ClientStorageText[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ClientStorageTextRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ClientStorageText::class);
    }

    /**
     * @return ClientStorageText[]
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

    public function findById(int $id): ?ClientStorageText
    {
        return $this->find($id);
    }
}
