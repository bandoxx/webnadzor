<?php

namespace App\Controller\ClientSetting\API;

use App\Repository\ClientRepository;
use App\Service\Client\ClientSettingsUpdater;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/api/{clientId}/settings', name: 'api_client_settings_update')]
class ClientSettingUpdateController extends AbstractController
{

    public function __invoke(int $clientId, Request $request, ClientSettingsUpdater $clientSettingsUpdater, ClientRepository $clientRepository): Response|NotFoundHttpException
    {
        $client = $clientRepository->find($clientId);
        if (!$client) {
            return $this->createNotFoundException('Client not found');
        }

        $clientSettingsUpdater->update($client, $request);

        return $this->redirectToRoute('client_overview', ['clientId' => $clientId]);
    }

}