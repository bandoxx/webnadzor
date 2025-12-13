<?php

namespace App\Controller\DeviceData;

use App\Entity\Client;
use App\Entity\Device;
use App\Repository\DeviceDataRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(
    path: '/admin/{clientId}/device/{id}/{entry}/modify',
    name: 'app_device_data_modify',
    methods: ['GET']
)]
class DeviceDataModifyController extends AbstractController
{
    public function __construct(
        private readonly DeviceDataRepository $deviceDataRepository,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    public function __invoke(
        #[MapEntity(id: 'clientId')]
        Client $client,
        #[MapEntity(id: 'id')]
        Device $device,
        int $entry,
        Request $request
    ): Response {
        $page = max(1, $request->query->getInt('page', 1));
        $limit = 50;
        $offset = ($page - 1) * $limit;

        $dateFrom = $request->query->get('date_from');
        $dateTo = $request->query->get('date_to');

        $dateFromObj = null;
        $dateToObj = null;

        if ($dateFrom) {
            try {
                $dateFromObj = \DateTime::createFromFormat('d.m.Y', $dateFrom);
                if ($dateFromObj) {
                    $dateFromObj->setTime(0, 0, 0);
                }
            } catch (\Exception $e) {
                $dateFromObj = null;
            }
        }

        if ($dateTo) {
            try {
                $dateToObj = \DateTime::createFromFormat('d.m.Y', $dateTo);
                if ($dateToObj) {
                    $dateToObj->setTime(23, 59, 59);
                }
            } catch (\Exception $e) {
                $dateToObj = null;
            }
        }

        $qb = $this->entityManager->createQueryBuilder()
            ->select('dd')
            ->from('App\Entity\DeviceData', 'dd')
            ->where('dd.device = :device')
            ->setParameter('device', $device)
            ->orderBy('dd.deviceDate', 'DESC');

        if ($dateFromObj) {
            $qb->andWhere('dd.deviceDate >= :dateFrom')
               ->setParameter('dateFrom', $dateFromObj);
        }

        if ($dateToObj) {
            $qb->andWhere('dd.deviceDate <= :dateTo')
               ->setParameter('dateTo', $dateToObj);
        }

        $countQb = clone $qb;
        $totalRecords = $countQb->select('COUNT(dd.id)')->getQuery()->getSingleScalarResult();

        $deviceData = $qb
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        $totalPages = (int) ceil($totalRecords / $limit);

        return $this->render('v2/device/device_data_modify.html.twig', [
            'client' => $client,
            'device' => $device,
            'entry' => $entry,
            'deviceData' => $deviceData,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalRecords' => $totalRecords,
            'dateFrom' => $dateFromObj,
            'dateTo' => $dateToObj,
        ]);
    }
}
