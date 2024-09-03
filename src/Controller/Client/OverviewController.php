<?php

namespace App\Controller\Client;

use App\Entity\User;
use App\Repository\ClientFtpRepository;
use App\Repository\ClientRepository;
use App\Repository\ClientSettingRepository;
use App\Repository\ClientStorageRepository;
use App\Service\ClientStorage\ScadaFactory;
use App\Service\DeviceLocationHandler;
use App\Service\PermissionChecker;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
        ClientFtpRepository $clientFtpRepository,
        ClientStorageRepository $clientStorageRepository,
        ScadaFactory $scadaFactory
    ): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $client = $clientRepository->find($clientId);

        if (!$client || PermissionChecker::isValid($user, $client) === false) {
            throw $this->createAccessDeniedException();
        }

        foreach ($client->getClientStorages()->toArray() as $clientStorage) {
            $clientStorages[] = $scadaFactory->createFromClientStorage($clientStorage);
        }

        return $this->render('v2/overview/user.html.twig', [
            'devices_table' => $deviceLocationHandler->getClientDeviceLocationData($user, $client),
            'settings' => $clientSettingRepository->findOneBy(['client' => $clientId]),
            'ftp' => $clientFtpRepository->findOneBy(['client' => $clientId]),
            'client_storages' => $clientStorages ?? []
        ]);
    }

}