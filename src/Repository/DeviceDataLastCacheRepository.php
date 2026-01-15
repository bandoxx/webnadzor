<?php

namespace App\Repository;

use App\Entity\Device;
use App\Entity\DeviceDataLastCache;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DeviceDataLastCache>
 */
class DeviceDataLastCacheRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DeviceDataLastCache::class);
    }

    /**
     * Fetch all cache entries for given devices in a single query.
     *
     * @param Device[] $devices
     * @return array<int, array<int, DeviceDataLastCache>> Indexed by [deviceId][entry]
     */
    public function findByDevicesIndexed(array $devices): array
    {
        if (empty($devices)) {
            return [];
        }

        $deviceIds = array_map(fn(Device $d) => $d->getId(), $devices);

        $caches = $this->createQueryBuilder('c')
            ->where('c.device IN (:deviceIds)')
            ->setParameter('deviceIds', $deviceIds)
            ->getQuery()
            ->getResult();

        $indexed = [];
        /** @var DeviceDataLastCache $cache */
        foreach ($caches as $cache) {
            $deviceId = $cache->getDevice()->getId();
            $entry = $cache->getEntry();
            $indexed[$deviceId][$entry] = $cache;
        }

        return $indexed;
    }
}
