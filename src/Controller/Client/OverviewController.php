<?php

namespace App\Controller\Client;

use App\Repository\ClientFtpRepository;
use App\Repository\ClientRepository;
use App\Repository\ClientSettingRepository;
use App\Service\DeviceLocationHandler;
use App\Service\PermissionChecker;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/admin/{clientId}/overview', name: 'client_overview')]
class OverviewController extends AbstractController
{
    public function __invoke(
        int $clientId,
        ClientRepository $clientRepository,
        DeviceLocationHandler $deviceLocationHandler,
        ClientSettingRepository $clientSettingRepository,
        ClientFtpRepository $clientFtpRepository
    ): Response
    {
        $client = $clientRepository->find($clientId);

        if (!$client || PermissionChecker::isValid($this->getUser(), $client) === false) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('v2/overview/user.html.twig', [
            'devices_table' => $deviceLocationHandler->getClientDeviceLocationData($this->getUser(), $client),
            'settings' => $clientSettingRepository->findOneBy(['client' => $clientId]),
            'ftp' => $clientFtpRepository->findOneBy(['client' => $clientId])
        ]);
    }

}