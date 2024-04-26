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

    public function binaryFindOneByName(string $xmlName)
    {
        return $this->createQueryBuilder('d')
            ->where('BINARY(d.xmlName) = :xmlName')->setParameter('xmlName', $xmlName)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function binaryFindByName(string $xmlName)
    {
        return $this->createQueryBuilder('d')
            ->where('BINARY(d.xmlName) = :xmlName')->setParameter('xmlName', $xmlName)
            ->getQuery()
            ->getResult()
        ;
    }

    public function findDevicesByClient($clientId)
    {
        return $this->findBy(['client' => $clientId]);
    }

    public function doesMoreThenOneXmlNameExists(string $xmlName)
    {
        $devices = $this->binaryFindByName($xmlName);

        return count($devices) > 1; // more than one, because if xml is not changed, current one has already that name
    }

//    /**
//     * @return DeviceConfig[] Returns an array of DeviceConfig objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('d')
//            ->andWhere('d.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('d.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?DeviceConfig
//    {
//        return $this->createQueryBuilder('d')
//            ->andWhere('d.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
