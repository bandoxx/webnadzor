<?php

namespace App\Controller\Overview\API;

use App\Repository\ClientRepository;
use App\Service\Client\ClientUpdater;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/api/client/{clientId}', name: 'api_client_update', methods: 'POST')]
class ClientUpdateController extends AbstractController
{

    public function __invoke(int $clientId, Request $request, ClientUpdater $clientUpdater, ClientRepository $clientRepository): RedirectResponse|JsonResponse
    {
        $client = $clientRepository->find($clientId);

        if (!$client) {
            return $this->json(null, Response::HTTP_BAD_REQUEST);
        }

        $clientUpdater->updateByRequest($request, $client);

        return $this->redirectToRoute('admin_overview');
    }

}