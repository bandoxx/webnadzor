<?php

namespace App\Controller\MapMarkers\API;

use App\Entity\User;
use App\Factory\MapMarkerFactory;
use App\Repository\ClientRepository;
use App\Service\Device\UserAccess;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/map/markers', name: 'api_map_markers_all_clients')]
class MapMarkersAllClientsController extends AbstractController
{
    public function __invoke(ClientRepository $clientRepository, UserAccess $userAccess, MapMarkerFactory $mapMarkerFactory): NotFoundHttpException|JsonResponse
    {
        /** @var User|null $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->createNotFoundException();
        }

        if ($user->getPermission() === User::ROLE_ADMINISTRATOR) {
            $clients = $user->getClients();
        } else {
            $clients = $clientRepository->findAllActive();
        }

        $data = [];

        foreach ($clients as $client) {
            $data[] = $mapMarkerFactory->create($client, $user);
        }

        return $this->json(['places' => array_merge(...$data)], Response::HTTP_OK);
    }

}