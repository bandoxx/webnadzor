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
        return $this->findBy(['user' => $user]);
    }
}
