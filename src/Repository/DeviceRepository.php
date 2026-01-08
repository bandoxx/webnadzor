<?php

namespace App\Repository;

use App\Entity\Client;
use App\Entity\Device;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Device>
 *
 * @method Device|null find($id, $lockMode = null, $lockVersion = null)
 * @method Device|null findOneBy(array $criteria, array $orderBy = null)
 * @method Device[]    findAll()
 * @method Device[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DeviceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Device::class);
    }

    public function binaryFindOneByName(string $xmlName): ?Device
    {
        $result = $this->createQueryBuilder('d')
            ->where('BINARY(d.xmlName) = :xmlName')->setParameter('xmlName', $xmlName)
            ->getQuery()
            ->getResult()
        ;
    
        return !empty($result) ? $result[0] : null;
    }

    public function deleteDevice(int $deviceId): void
    {
        $this->getEntityManager()->getConnection()->executeStatement(
            "DELETE FROM device WHERE id = :deviceId",
            ['deviceId' => $deviceId]
        );
    }

    /**
     * @return array<Device>
     */
    public function findDevicesByClient(int $clientId, bool $filled = false): array
    {
        $qb = $this->createQueryBuilder('d')
            ->where('d.client = :clientId')
            ->andWhere('d.isDeleted = :isDeleted')
            ->setParameter('clientId', $clientId)
            ->setParameter('isDeleted', false);
        if ($filled) {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->isNotNull('d.simCardProvider'),
                    $qb->expr()->isNotNull('d.simPhoneNumber')
                )
            );
        }
        return $qb->getQuery()->getResult();
    }


    public function doesMoreThenOneXmlNameExists(string $xmlName): bool
    {
        $numberOfDevicesWithName = $this->createQueryBuilder('d')
            ->select('COUNT(d)')
            ->where('BINARY(d.xmlName) = :name')->setParameter('name', $xmlName)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return $numberOfDevicesWithName > 0;
    }

    public function doesMoreThanOneSerialNumberExists(string $serialNumber): bool
    {
        $numberOfDevicesWithName = $this->createQueryBuilder('d')
            ->select('COUNT(d)')
            ->where('BINARY(d.serialNumber) = :name')->setParameter('name', $serialNumber)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return $numberOfDevicesWithName > 0;
    }

    public function findActiveDevices(bool $filled = false): array
    {
        $qb = $this->createQueryBuilder('d')
            ->where('d.isDeleted = :isDeleted')
            ->setParameter('isDeleted', false);
        if ($filled) {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->isNotNull('d.simCardProvider'),
                    $qb->expr()->isNotNull('d.simPhoneNumber')
                )
            );
        }
        return $qb->getQuery()->getResult();
    }

    /**
     * Finds all devices that have either xmlName or serialNumber (not empty and not null)
     *
     * @return array<Device>
     */
    public function findDevicesWithIdentifiers(): array
    {
        $qb = $this->createQueryBuilder('d');
        return $qb
            ->where('d.isDeleted = :isDeleted')
            ->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->andX(
                        $qb->expr()->isNotNull('d.xmlName'),
                        $qb->expr()->neq('d.xmlName', ':emptyString')
                    ),
                    $qb->expr()->andX(
                        $qb->expr()->isNotNull('d.serialNumber'),
                        $qb->expr()->neq('d.serialNumber', ':emptyString')
                    )
                )
            )
            ->setParameter('isDeleted', false)
            ->setParameter('emptyString', '')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Get devices for dropdown (lightweight - only id, name, client name)
     * @return array<array{id: int, name: string, client_name: string|null}>
     */
    public function findForDropdown(): array
    {
        return $this->createQueryBuilder('d')
            ->select('d.id', 'd.name', 'c.name as client_name')
            ->leftJoin('d.client', 'c')
            ->where('d.isDeleted = :isDeleted')
            ->setParameter('isDeleted', false)
            ->orderBy('c.name', 'ASC')
            ->addOrderBy('d.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }
}
