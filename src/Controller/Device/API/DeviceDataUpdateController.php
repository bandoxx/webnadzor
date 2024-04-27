<?php

namespace App\Controller\Device\API;

use App\Repository\DeviceDataRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/api/device/data/{id}/{entry}', name: 'api_device_data_update', methods: 'PATCH')]
class DeviceDataUpdateController extends AbstractController
{

    public function __invoke(int $id, int $entry, Request $request, DeviceDataRepository $deviceDataRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $note = $request->get('note');

        $deviceData = $deviceDataRepository->find($id);

        if (!$deviceData) {
            return $this->json(false, Response::HTTP_NOT_FOUND);
        }

        $deviceData->setNote($entry, $note);

        $entityManager->flush();

        return $this->json(true, Response::HTTP_CREATED);

    }

}