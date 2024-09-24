<?php

namespace App\Controller\Overview\API;

use App\Entity\Client;
use App\Service\Client\ClientUpdater;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/api/client', name: 'api_client_create', methods: 'POST')]
class ClientCreateController extends AbstractController
{

    public function __invoke(Request $request, ClientUpdater $clientUpdater): RedirectResponse
    {
        $clientUpdater->updateByRequest($request, new Client());

        return $this->redirectToRoute('admin_overview');
    }

}