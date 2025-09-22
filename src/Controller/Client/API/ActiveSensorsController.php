<?php

namespace App\Controller\Client\API;

use App\Entity\User;
use App\Repository\ClientRepository;
use App\Repository\DeviceRepository;
use App\Service\DeviceLocationHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/clients/active-sensors', name: 'api_clients_active_sensors', methods: ['GET'])]
class ActiveSensorsController extends AbstractController
{
    public function __invoke(Request $request, ClientRepository $clientRepository, DeviceLocationHandler $deviceLocationHandler): JsonResponse
    {
        /** @var User|null $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        // Read clientIds from query parameters. Supports:
        // - clientIds[]=1
        // - clientIds[]=2
        $clientIds = $request->query->all('clientIds');

        if (!is_array($clientIds)) {
            return $this->json(['error' => 'clientIds must be provided as query parameter'], Response::HTTP_BAD_REQUEST);
        }

        // Normalize and filter IDs
        $clientIds = array_values(array_unique(array_filter(array_map(static function ($v) {
            if (is_string($v) && ctype_digit($v)) return (int)$v;
            if (is_int($v)) return $v;
            return null;
        }, $clientIds), static fn($v) => $v !== null)));

        if (empty($clientIds)) {
            return $this->json(['clients' => []], Response::HTTP_OK);
        }

        $clients = $clientRepository->findActiveByIds($clientIds);

        $result = [];
        foreach ($clients as $client) {
            $result[] = $deviceLocationHandler->getClientDeviceLocations($client->getId(), true);
        }

        return $this->json(array_merge(...$result), Response::HTTP_OK);
    }
}
