<?php

namespace App\Repository;

use App\Entity\Device;
use App\Entity\DeviceDataArchive;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DeviceDataArchive>
 *
 * @method DeviceDataArchive|null find($id, $lockMode = null, $lockVersion = null)
 * @method DeviceDataArchive|null findOneBy(array $criteria, array $orderBy = null)
 * @method DeviceDataArchive[]    findAll()
 * @method DeviceDataArchive[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DeviceDataArchiveRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DeviceDataArchive::class);
    }

    public function getDailyArchives(Device $device, int $entry, ?\DateTime $dateFrom = null, ?\DateTime $dateTo = null): array
    {
        $qb = $this->createQueryBuilder('dda')
            ->where('dda.device = :device_id')
            ->andWhere('dda.entry = :entry')
            ->andWhere('dda.period = :period')
            ->setParameter('device_id', $device->getId())
            ->setParameter('entry', $entry)
            ->setParameter('period', DeviceDataArchive::PERIOD_DAY)
            ->orderBy('dda.archiveDate', 'DESC');

        if ($dateFrom !== null && $dateTo !== null) {
            $qb->andWhere('dda.archiveDate BETWEEN :date_from AND :date_to')
                ->setParameter('date_from', $dateFrom)
                ->setParameter('date_to', $dateTo);
        }

        return $qb->getQuery()->getResult();
    }

    public function getMonthlyArchives(Device $device, int $entry, ?\DateTime $dateFrom = null, ?\DateTime $dateTo = null): array
    {
        $qb = $this->createQueryBuilder('dda')
            ->where('dda.device = :device_id')
            ->andWhere('dda.entry = :entry')
            ->andWhere('dda.period = :period')
            ->setParameter('device_id', $device->getId())
            ->setParameter('entry', $entry)
            ->setParameter('period', DeviceDataArchive::PERIOD_MONTH)
            ->orderBy('dda.archiveDate', 'DESC');

        if ($dateFrom !== null && $dateTo !== null) {
            $qb->andWhere('dda.archiveDate BETWEEN :date_from AND :date_to')
                ->setParameter('date_from', $dateFrom)
                ->setParameter('date_to', $dateTo);
        }

        return $qb->getQuery()->getResult();
    }

    public function deleteArchiveRelatedToDevice(int $deviceId): void
    {
        $this->getEntityManager()->getConnection()->executeQuery(
            "DELETE FROM device_data_archive WHERE device_id = $deviceId",
        )->free();
    }
}
