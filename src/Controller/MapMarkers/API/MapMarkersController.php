<?php

namespace App\Controller\MapMarkers\API;

use App\Entity\Client;
use App\Entity\User;
use App\Factory\MapMarkerFactory;
use App\Repository\ClientRepository;
use App\Service\Device\UserAccess;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/map/markers/{clientId}', name: 'api_map_markers')]
class MapMarkersController extends AbstractController
{
    public function __invoke(
        #[MapEntity(id: 'clientId')]
        Client $client,
        ClientRepository $clientRepository,
        UserAccess $userAccess,
        MapMarkerFactory $mapMarkerFactory
    ): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->json(['places' => $mapMarkerFactory->create($client, $user)], Response::HTTP_OK);
    }

}