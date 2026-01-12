<?php

namespace App\Repository;

use App\Entity\Client;
use App\Entity\Device;
use App\Entity\DeviceAlarm;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DeviceAlarm>
 *
 * @method DeviceAlarm|null find($id, $lockMode = null, $lockVersion = null)
 * @method DeviceAlarm|null findOneBy(array $criteria, array $orderBy = null)
 * @method DeviceAlarm[]    findAll()
 * @method DeviceAlarm[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DeviceAlarmRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DeviceAlarm::class);
    }

    public function deleteAlarmsRelatedToDevice(int $deviceId): void
    {
        $this->getEntityManager()->getConnection()->executeStatement(
            "DELETE FROM device_alarm WHERE device_id = :deviceId",
            ['deviceId' => $deviceId]
        );
    }

    public function findByDeviceOrderByEndDate(Device $device, int $entry): array
    {
        return array_merge([...$this->findActiveAlarms($device, $entry), ...$this->findDeactivatedAlarms($device, $entry)]);
    }

    public function findByDevice(Device $device): array
    {
        return $this->findBy(['device' => $device], ['deviceDate' => 'DESC']);
    }

    /**
     * @return DeviceAlarm[]
     */
    public function findActiveAlarms(Device $device, ?int $sensor = null): array
    {
        $builder = $this->createQueryBuilder('a');

        if ($sensor === null) {
            $builder->where('a.device = :device AND a.endDeviceDate IS NULL');
        } else {
            $builder->where('a.device = :device AND a.endDeviceDate IS NULL AND (a.sensor = :sensor OR a.sensor IS NULL)')
                ->setParameter('sensor', $sensor)
            ;
        }

        return $builder->setParameter('device', $device)
            ->orderBy('a.deviceDate', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findDeactivatedAlarms(Device $device, ?int $sensor = null): array
    {
        $builder = $this->createQueryBuilder('a');

        if ($sensor === null) {
            $builder->where('a.device = :device AND a.endDeviceDate IS NOT NULL');
        } else {
            $builder->where('a.device = :device AND a.endDeviceDate IS NOT NULL AND (a.sensor = :sensor OR a.sensor IS NULL)')
                ->setParameter('sensor', $sensor)
            ;
        }

        return $builder->setParameter('device', $device)
            ->orderBy('a.endDeviceDate', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @param Device $device
     * @param int|null $entry
     * @return DeviceAlarm[]
     */
    public function findAlarmsThatNeedsNotification(Device $device, ?int $entry = null): array
    {
        $queryBuilder = $this->createQueryBuilder('a');

        if (empty($entry)) {
            return $queryBuilder
                ->where('a.device = :device AND a.endDeviceDate IS NULL AND a.isNotified = false AND a.sensor IS NULL')
                ->setParameter('device', $device)
                ->getQuery()
                ->getResult()
            ;
        }

        return $queryBuilder
            ->where('a.device = :device AND a.endDeviceDate IS NULL AND a.isNotified = false AND a.sensor = :sensor')
            ->setParameter('device', $device)
            ->setParameter('sensor', $entry)
            ->getQuery()
            ->getResult()
        ;
    }

    public function findActiveAlarm(Device $device, string $type, ?int $sensor = null): ?DeviceAlarm
    {
        $builder = $this->createQueryBuilder('a');

        $builder
            ->where('a.device = :device AND a.endDeviceDate IS NULL AND a.type = :type')
            ->setParameter('device', $device->getId())
            ->setParameter('type', $type)
        ;

        if ($sensor) {
            $builder->andWhere('a.sensor = :sensor')->setParameter('sensor', $sensor);
        } else {
            $builder->andWhere('a.sensor IS NULL');
        }

        $result = $builder
            ->getQuery()
            ->getResult()
        ;
        
        return !empty($result) ? $result[0] : null;
    }

    public function findNumberOfActiveAlarmsForDevice(Device $device, ?int $entry = null): int
    {
        $builder = $this->createQueryBuilder('a')
            ->select('COUNT(a)')
            ->where('a.device = :device AND a.endDeviceDate IS NULL')->setParameter('device', $device->getId())
        ;

        if ($entry) {
            $builder->andWhere('a.sensor = :sensor')->setParameter('sensor', $entry);
        }

        return $builder
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    public function findOlderThen(int $months): array
    {
        return $this->createQueryBuilder('da')
            ->where('da.deviceDate < :date AND da.endDeviceDate IS NOT NULL')
            ->setParameter('date', new \DateTime("-$months months"))
            ->getQuery()
            ->getResult()
        ;
    }

    private const OFFLINE_ALARM_TYPES = ['device-offline', 'device-sensor-missing-data'];

    /**
     * @return DeviceAlarm[]
     */
    public function findOfflineAlarms(?Client $client = null, ?Device $device = null, ?\DateTimeInterface $dateFrom = null, ?\DateTimeInterface $dateTo = null, ?int $limit = null): array
    {
        $builder = $this->createQueryBuilder('a')
            ->select('a', 'd', 'c')
            ->leftJoin('a.device', 'd')
            ->leftJoin('d.client', 'c')
            ->where('a.type IN (:types)')
            ->setParameter('types', self::OFFLINE_ALARM_TYPES)
            ->orderBy('a.deviceDate', 'DESC')
        ;

        if ($client !== null) {
            $builder->andWhere('d.client = :client')
                ->setParameter('client', $client);
        }

        if ($device !== null) {
            $builder->andWhere('a.device = :device')
                ->setParameter('device', $device);
        }

        if ($dateFrom !== null) {
            $builder->andWhere('a.deviceDate >= :dateFrom')
                ->setParameter('dateFrom', $dateFrom);
        }

        if ($dateTo !== null) {
            $builder->andWhere('a.deviceDate <= :dateTo')
                ->setParameter('dateTo', $dateTo);
        }

        if ($limit !== null) {
            $builder->setMaxResults($limit);
        }

        return $builder->getQuery()->getResult();
    }

    public function countActiveOfflineAlarms(): int
    {
        return (int) $this->createQueryBuilder('a')
            ->select('COUNT(a)')
            ->where('a.type IN (:types)')
            ->andWhere('a.endDeviceDate IS NULL')
            ->setParameter('types', self::OFFLINE_ALARM_TYPES)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    public function countOfflineAlarmsInRange(?\DateTimeInterface $dateFrom = null, ?\DateTimeInterface $dateTo = null): int
    {
        $builder = $this->createQueryBuilder('a')
            ->select('COUNT(a)')
            ->where('a.type IN (:types)')
            ->setParameter('types', self::OFFLINE_ALARM_TYPES)
        ;

        if ($dateFrom !== null) {
            $builder->andWhere('a.deviceDate >= :dateFrom')
                ->setParameter('dateFrom', $dateFrom);
        }

        if ($dateTo !== null) {
            $builder->andWhere('a.deviceDate <= :dateTo')
                ->setParameter('dateTo', $dateTo);
        }

        return (int) $builder->getQuery()->getSingleScalarResult();
    }

    /**
     * Fetch all alarms that need notification in a single query.
     * Returns alarms grouped by device ID and sensor.
     *
     * @return array<int, array{main: DeviceAlarm[], entry1: DeviceAlarm[], entry2: DeviceAlarm[]}>
     */
    public function findAllAlarmsThatNeedNotification(): array
    {
        $alarms = $this->createQueryBuilder('a')
            ->select('a', 'd')
            ->leftJoin('a.device', 'd')
            ->where('a.endDeviceDate IS NULL AND a.isNotified = false')
            ->getQuery()
            ->getResult()
        ;

        $grouped = [];
        foreach ($alarms as $alarm) {
            /** @var DeviceAlarm $alarm */
            $deviceId = $alarm->getDevice()->getId();
            $sensor = $alarm->getSensor();

            if (!isset($grouped[$deviceId])) {
                $grouped[$deviceId] = [
                    'device' => $alarm->getDevice(),
                    'main' => [],
                    'entry1' => [],
                    'entry2' => [],
                ];
            }

            if ($sensor === null) {
                $grouped[$deviceId]['main'][] = $alarm;
            } elseif ($sensor === 1) {
                $grouped[$deviceId]['entry1'][] = $alarm;
            } elseif ($sensor === 2) {
                $grouped[$deviceId]['entry2'][] = $alarm;
            }
        }

        return $grouped;
    }
}
