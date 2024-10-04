<?php

namespace App\Controller\ClientSetting\API;

use App\Entity\Client;
use App\Repository\ClientRepository;
use App\Service\Client\ClientSettingsUpdater;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/api/{clientId}/settings', name: 'api_client_settings_update')]
class ClientSettingUpdateController extends AbstractController
{

    public function __invoke(
        #[MapEntity(id: 'clientId')]
        Client $client,
        Request $request,
        ClientSettingsUpdater $clientSettingsUpdater
    ): Response|NotFoundHttpException
    {
        $clientSettingsUpdater->update($client, $request);

        return $this->redirectToRoute('client_overview', ['clientId' => $client->getId()]);
    }

}