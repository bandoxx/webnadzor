<?php

namespace App\Controller\ClientSetting;

use App\Repository\ClientSettingRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/admin/{clientId}/settings', name: 'admin_client_settings')]
class ClientSettingController extends AbstractController
{

    public function __invoke(int $clientId, ClientSettingRepository $clientSettingRepository): Response
    {
        $settings = $clientSettingRepository->findOneBy(['client' => $clientId]);
        if (!$settings) {
            throw new BadRequestException();
        }

        return $this->render('v1/client_setting/index.html.twig', [
            'settings' => $settings
        ]);
    }

}