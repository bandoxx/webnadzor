<?php

namespace App\Repository;

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
        $this->getEntityManager()->getConnection()->executeQuery(
            "DELETE FROM device_alarm WHERE device_id = $deviceId",
        )->free();
    }

    public function findByDevice(Device $device): array
    {
        return $this->findBy(['device' => $device], ['deviceDate' => 'DESC']);
    }

    /**
     * @return DeviceAlarm[]
     */
    public function findActiveAlarms(Device $device): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.device = :device AND a.endDeviceDate IS NULL')
            ->setParameter('device', $device)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @param Device $device
     * @return DeviceAlarm[]
     */
    public function findAlarmsThatNeedsNotification(Device $device): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.device = :device AND a.endDeviceDate IS NULL AND a.isNotified = false')
            ->setParameter('device', $device)
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

        return $builder
            ->getQuery()
            ->getOneOrNullResult()
        ;
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
}
