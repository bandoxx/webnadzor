<?php

namespace App\Controller\Device;

use App\Repository\ClientStorageDeviceRepository;
use App\Repository\ClientStorageRepository;
use App\Repository\ClientStorageTextRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/admin/{clientId}/clientstorage/{clientStorageId}/delete', name: 'app_client_storage_device_entry_delete', methods: 'DELETE')]

class CustomerStorageDeviceDeleteController extends AbstractController
{
    public function __invoke(int $clientId, int $clientStorageId,
        ClientStorageRepository $clientStorageRepository,
        ClientStorageDeviceRepository $clientStorageDeviceRepository,
        ClientStorageTextRepository $clientStorageTextRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse
    {

        $clientStorage = $clientStorageRepository->find($clientStorageId);

        if (!$clientStorage) {
            return $this->json('Icon not found', Response::HTTP_NOT_FOUND);
        }

        $clientStorageTextRepository->deleteByClientStorageId($clientStorageId);
        $clientStorageDeviceRepository->deleteByClientStorageId($clientStorageId);

        $entityManager->remove($clientStorage);
        $entityManager->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);

    }

}