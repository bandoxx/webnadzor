<?php

namespace App\Repository;

use App\Entity\Client;
use App\Entity\LoginLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LoginLog>
 *
 * @method LoginLog|null find($id, $lockMode = null, $lockVersion = null)
 * @method LoginLog|null findOneBy(array $criteria, array $orderBy = null)
 * @method LoginLog[]    findAll()
 * @method LoginLog[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LoginLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LoginLog::class);
    }

    public function findByClientAndForDay(Client $client, \DateTime $dateTime)
    {
        $start = (clone ($dateTime))->setTime(0, 0);
        $end = (clone ($dateTime))->setTime(23, 59);

        return $this->createQueryBuilder('ll')
            ->where('ll.client = :client_id')
            ->andWhere('ll.serverDate >= :start AND ll.serverDate <= :end')
            ->setParameter('client_id', $client->getid())
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->orderBy('ll.serverDate', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findOlderThen($months)
    {
        return $this->createQueryBuilder('ll')
            ->where('ll.serverDate < :date')
            ->setParameter('date', new \DateTime("-$months months"))
            ->getQuery()
            ->getResult()
        ;
    }

//    /**
//     * @return LoginLog[] Returns an array of LoginLog objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('l')
//            ->andWhere('l.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('l.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?LoginLog
//    {
//        return $this->createQueryBuilder('l')
//            ->andWhere('l.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
