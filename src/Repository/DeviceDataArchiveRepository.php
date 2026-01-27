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
            ->orderBy('dda.serverDate', 'DESC');

        if ($dateFrom !== null && $dateTo !== null) {
            $qb->andWhere('dda.serverDate BETWEEN :date_from AND :date_to')
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
        $this->getEntityManager()->getConnection()->executeStatement(
            "DELETE FROM device_data_archive WHERE device_id = :deviceId",
            ['deviceId' => $deviceId]
        );
    }

    public function archiveExists(Device $device, int $entry, \DateTime $archiveDate, string $period): bool
    {
        // Use DQL EXISTS for better performance
        $qb = $this->createQueryBuilder('dda')
            ->select('1')
            ->where('dda.device = :device_id')
            ->andWhere('dda.entry = :entry')
            ->andWhere('dda.period = :period')
            ->andWhere('dda.archiveDate = :archive_date')
            ->setParameter('device_id', $device->getId())
            ->setParameter('entry', $entry)
            ->setParameter('period', $period)
            ->setParameter('archive_date', $archiveDate)
            ->setMaxResults(1);

        return count($qb->getQuery()->getResult()) > 0;
    }

    /**
     * Delete daily archives for a device within a date range
     *
     * @param int $deviceId
     * @param \DateTimeInterface $dateFrom
     * @param \DateTimeInterface $dateTo
     * @param int|null $entry Entry number (1 or 2) to delete, null for both entries
     * @return int Number of archives deleted
     */
    public function deleteDailyArchivesForDeviceAndDateRange(
        int $deviceId,
        \DateTimeInterface $dateFrom,
        \DateTimeInterface $dateTo,
        ?int $entry = null
    ): int {
        $qb = $this->createQueryBuilder('dda')
            ->delete()
            ->where('dda.device = :device_id')
            ->andWhere('dda.period = :period')
            ->andWhere('dda.archiveDate >= :date_from')
            ->andWhere('dda.archiveDate <= :date_to')
            ->setParameter('device_id', $deviceId)
            ->setParameter('period', DeviceDataArchive::PERIOD_DAY)
            ->setParameter('date_from', $dateFrom)
            ->setParameter('date_to', $dateTo);

        // Only delete archives for the specific entry if provided
        if ($entry !== null) {
            $qb->andWhere('dda.entry = :entry')
                ->setParameter('entry', $entry);
        }

        return $qb->getQuery()->execute();
    }
}
