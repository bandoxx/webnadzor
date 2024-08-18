<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Factory\MapMarkerFactory;
use App\Repository\ClientRepository;
use App\Service\Device\UserAccess;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/map/markers/{clientId}', name: 'api_map_markers')]
class MapMarkersController extends AbstractController
{
    public function __invoke(int $clientId, ClientRepository $clientRepository, UserAccess $userAccess, MapMarkerFactory $mapMarkerFactory): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $client = $clientRepository->find($clientId);
        if (!$client) {
            throw new BadRequestHttpException();
        }

        return $this->json(['places' => $mapMarkerFactory->create($client, $user)], Response::HTTP_OK);
    }

}