<?php

namespace App\Controller\Device\API;

use App\Entity\Client;
use App\Entity\DeviceData;
use App\Repository\DeviceDataRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/api/{clientId}/device/data/{id}/{entry}', name: 'api_device_data_update', methods: 'POST')]
class DeviceDataUpdateController extends AbstractController
{

    public function __invoke(
        #[MapEntity(id: 'clientId')]
        Client $client,
        #[MapEntity(id: 'id')]
        DeviceData $deviceData,
        int $entry,
        Request $request,
        EntityManagerInterface $entityManager
    ): RedirectResponse
    {
        $note = $request->get('note');

        $device = $deviceData->getDevice();
        if (!$device) {
            return $this->redirectToRoute('app_device_list', ['clientId' => $client->getId()]);
        }

        $dateFrom = $request->query->get('date_from');
        $dateTo = $request->query->get('date_to');

        $deviceData->setNote($entry, $note);

        $entityManager->flush();

        return $this->redirectToRoute('app_device_export', ['clientId' => $client->getId(), 'id' => $device->getId(), 'entry' => $entry, 'date_from' => $dateFrom, 'date_to' => $dateTo]);

    }

}