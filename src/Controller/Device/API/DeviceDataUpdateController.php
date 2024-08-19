<?php

namespace App\Controller\Device\API;

use App\Repository\DeviceDataRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/api/{clientId}/device/data/{id}/{entry}', name: 'api_device_data_update', methods: 'POST')]
class DeviceDataUpdateController extends AbstractController
{

    public function __invoke(int $clientId, int $id, int $entry, Request $request, DeviceDataRepository $deviceDataRepository, EntityManagerInterface $entityManager): RedirectResponse
    {
        $note = $request->get('note');

        $deviceData = $deviceDataRepository->find($id);

        if (!$deviceData) {
            return $this->redirectToRoute('app_device_list', ['clientId' => $clientId]);
        }

        $device = $deviceData->getDevice();
        if (!$device) {
            return $this->redirectToRoute('app_device_list', ['clientId' => $clientId]);
        }

        $dateFrom = $request->query->get('date_from');
        $dateTo = $request->query->get('date_to');

        $deviceData->setNote($entry, $note);

        $entityManager->flush();

        return $this->redirectToRoute('app_device_export', ['clientId' => $clientId, 'id' => $device->getId(), 'entry' => $entry, 'date_from' => $dateFrom, 'date_to' => $dateTo]);

    }

}