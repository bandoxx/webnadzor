<?php

namespace App\Controller\Device\API;

use App\Repository\DeviceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/devices/entries', name: 'api_devices_entries_list', methods: ['GET'])]
class DeviceEntriesListController extends AbstractController
{
    public function __invoke(DeviceRepository $deviceRepository): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $devices = $deviceRepository->findActiveDevices();
        $result = [];

        foreach ($devices as $device) {
            // Check both entries (1 and 2)
            for ($entry = 1; $entry <= 2; $entry++) {
                // Check if either temperature or humidity is used for this entry
                if ($device->isTUsed($entry) || $device->isRhUsed($entry)) {
                    $entryData = $device->getEntryData($entry);

                    // Prefer t_name, fallback to rh_name if t is not used
                    $entryName = $device->isTUsed($entry) && !empty($entryData['t_name'])
                        ? $entryData['t_name']
                        : ($entryData['rh_name'] ?? '');

                    // Format: "device.name - entry.t_name" (or rh_name)
                    $result[] = [
                        'label' => $device->getName() . ' - ' . $entryName,
                        'deviceId' => $device->getId(),
//                        'deviceName' => $device->getName(),
//                        'entry' => $entry,
//                        'entryName' => $entryName,
                    ];
                }
            }
        }

        return $this->json($result, Response::HTTP_OK);
    }
}
