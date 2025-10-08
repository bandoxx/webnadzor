<?php

namespace App\Repository;

use App\Entity\Client;
use App\Entity\DeviceAlarmLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DeviceAlarmLog>
 */
class DeviceAlarmLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DeviceAlarmLog::class);
    }

    /**
     * @return array<DeviceAlarmLog>
     */
    public function findByDates(Client $client, ?\DateTime $dateFrom = null, ?\DateTime $dateTo = null): array
    {
        $queryBuilder = $this->createQueryBuilder('a')
            ->where('a.client = :client')
            ->setParameter('client', $client)
        ;

        if ($dateFrom) {
            $dateFrom->setTime(0, 0, 0);

            $queryBuilder->andWhere('a.createdAt >= :dateFrom')
                ->setParameter('dateFrom', $dateFrom)
            ;
        }

        if ($dateFrom && $dateTo) {
            $dateTo->setTime(23, 59, 59);

            $queryBuilder->andWhere('a.createdAt <= :dateTo')
                ->setParameter('dateTo', $dateTo)
            ;
        }

        return $queryBuilder
            ->getQuery()
            ->getResult()
        ;
    }

    public function deleteLogsRelatedToDevice(int $deviceId): void
    {
        $this->getEntityManager()->getConnection()->executeQuery(
            'DELETE dal FROM device_alarm_log dal JOIN device_alarm da ON dal.device_alarm_id = da.id WHERE da.device_id = :deviceId',
            ['deviceId' => $deviceId]
        )->free();
    }
}
