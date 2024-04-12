<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\UserDeviceAccess;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserDeviceAccess>
 *
 * @method UserDeviceAccess|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserDeviceAccess|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserDeviceAccess[]    findAll()
 * @method UserDeviceAccess[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserDeviceAccessRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserDeviceAccess::class);
    }

    public function deleteAccessesRelatedToDevice(int $deviceId): void
    {
        $this->getEntityManager()->getConnection()->executeQuery(
            "DELETE FROM user_device_access WHERE device_id = $deviceId",
        )->free();
    }

    public function findAccessibleEntries(User $user): array
    {
        $accesses = $this->findBy(['user' => $user]);

        $data = [];

        foreach ($accesses as $access) {
            $data[] = [
                'entry' => $access->getSensor(),
                'device' => $access->getDevice()
            ];
        }

        return $data;
    }

//    /**
//     * @return UserDeviceAccess[] Returns an array of UserDeviceAccess objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('u.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?UserDeviceAccess
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
