<?php

namespace App\Controller\Device;

use App\Entity\Client;
use App\Entity\Device;
use App\Factory\DeviceOverviewFactory;
use App\Repository\DeviceRepository;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/admin/{clientId}/device/{id}/{entry}/show', name: 'app_device_entry_show', methods: 'GET')]
class DeviceShowByEntryController extends AbstractController
{

    public function __invoke(
        #[MapEntity(id: 'clientId')]
        Client $client,
        #[MapEntity(id: 'id')]
        Device $device,
        int $entry,
        DeviceOverviewFactory $deviceOverviewFactory,
        DeviceRepository $deviceRepository,
    ): Response
    {
        $deviceOverview = $deviceOverviewFactory->create($device, $entry);

        return $this->render('v2/device/device_sensor_show.html.twig', [
            'device' => $deviceOverview,
            'entry' => $entry,
        ]);
    }

}