<?php

namespace App\Controller\DeviceData\API;

use App\Entity\DeviceData;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(
    path: '/api/device-data/{id}/delete',
    name: 'api_device_data_delete',
    methods: ['DELETE']
)]
class DeviceDataDeleteController extends AbstractController
{
    public function __invoke(
        #[MapEntity(id: 'id')]
        DeviceData $deviceData,
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

        $deviceDataId = $deviceData->getId();

        $entityManager->remove($deviceData);
        $entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Zapis uspješno obrisan.',
            'deletedId' => $deviceDataId,
        ]);
    }
}
