<?php

namespace App\Controller\Device;

use App\Factory\DeviceSimListFactory;
use App\Model\Device\DeviceSimListItem;
use App\Repository\DeviceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;

#[Route('/admin/{clientId}/device/sim-list', name: 'app_client_device_sim_list', methods: 'GET')]
class DeviceSimListController extends AbstractController
{

    public function __invoke(int $clientId, DeviceRepository $deviceRepository, DeviceSimListFactory $deviceSimListFactory, Request $request): Response
    {
        $filled = $request->query->getBoolean('filled', false);
        $devices = $deviceRepository->findDevicesByClient($clientId, $filled);

        /** @var DeviceSimListItem[] $table */
        $table = [];

        foreach ($devices as $device) {
            $table[] = $deviceSimListFactory->create($device);
        }

        return $this->render('v2/device/device_sim_list.html.twig', [
            'clientId' => $clientId,
            'list' => $table
        ]);
    }

}