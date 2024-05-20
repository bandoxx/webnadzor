<?php

namespace App\Controller\ClientSetting\API;

use App\Repository\ClientSettingRepository;
use App\Service\Client\ClientSettingsUpdater;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/api/{clientId}/settings', name: 'api_client_settings_update')]
class ClientSettingUpdateController extends AbstractController
{

    public function __invoke(int $clientId, Request $request, ClientSettingsUpdater $clientSettingsUpdater, ClientSettingRepository $clientSettingRepository): Response
    {
        $settings = $clientSettingRepository->findOneBy(['client' => $clientId]);

        if (!$settings) {
            throw new BadRequestException();
        }

        $clientSettingsUpdater->update($settings, $request);

        return $this->json(true, Response::HTTP_ACCEPTED);
    }

}