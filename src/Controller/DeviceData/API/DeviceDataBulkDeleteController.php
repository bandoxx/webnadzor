<?php

namespace App\Controller\DeviceData\API;

use App\Entity\DeviceData;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(
    path: '/api/device-data/bulk-delete',
    name: 'api_device_data_bulk_delete',
    methods: ['POST']
)]
class DeviceDataBulkDeleteController extends AbstractController
{
    public function __invoke(
        Request $request,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();

        if ($user->getPermission() !== 4) {
            return $this->json([
                'success' => false,
                'message' => 'Nemate dozvolu za ovu akciju.',
            ], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);
        $ids = $data['ids'] ?? [];

        if (empty($ids) || !is_array($ids)) {
            return $this->json([
                'success' => false,
                'message' => 'Nije odabran nijedan zapis za brisanje.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $deviceDataRepository = $entityManager->getRepository(DeviceData::class);
        $deletedIds = [];
        $batchSize = 50;
        $i = 0;

        foreach ($ids as $id) {
            $deviceData = $deviceDataRepository->find((int) $id);
            if ($deviceData) {
                $deletedIds[] = $deviceData->getId();
                $entityManager->remove($deviceData);
                $i++;

                if ($i % $batchSize === 0) {
                    $entityManager->flush();
                }
            }
        }

        $entityManager->flush();

        $deletedCount = count($deletedIds);

        return $this->json([
            'success' => true,
            'message' => sprintf('Uspješno obrisano %d zapis(a).', $deletedCount),
            'deletedIds' => $deletedIds,
            'deletedCount' => $deletedCount,
        ]);
    }
}
