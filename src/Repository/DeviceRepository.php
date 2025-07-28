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
        return $this->createQueryBuilder('d')
            ->where('BINARY(d.xmlName) = :xmlName')->setParameter('xmlName', $xmlName)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function deleteDevice(int $deviceId): void
    {
        $this->getEntityManager()->getConnection()->executeQuery(
            "DELETE FROM device WHERE id = $deviceId",
        )->free();
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

}
