<?php

namespace App\Controller\Overview;

use App\Entity\User;
use App\Repository\AdminOverviewCacheRepository;
use App\Repository\ClientRepository;
use App\Repository\SmtpRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/overview', name: 'admin_overview')]
class AdminOverview extends AbstractController
{
    public function __invoke(ClientRepository $clientRepository, AdminOverviewCacheRepository $cacheRepository, SmtpRepository $smtpRepository): RedirectResponse|\Symfony\Component\HttpFoundation\Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $clients = $clientRepository->findAllActive();

        if ($user->getClients()->count() === 1) {
            $clientId = $user->getclients()->first()->getId();

            return $this->redirectToRoute('client_overview', [
                'clientId' => $clientId,
            ]);
        }

        $data = [];
        foreach ($clients as $client) {
            if ($user->isUser() || $user->isModerator() || $user->getClients()->contains($client) === false) {
                continue;
            }

            $clientId = $client->getId();
            $cache = $cacheRepository->findOneByClient($client);

            $data[$client->getId()] = [
                'id' => $clientId,
                'name' => $client->getName(),
                'address' => $client->getAddress(),
                'oib' => $client->getOIB(),
                'numberOfDevices' => $cache?->getNumberOfDevices() ?? 0,
                'onlineDevices' => $cache?->getOnlineDevices() ?? 0,
                'offlineDevices' => $cache?->getOfflineDevices() ?? 0,
                'overview' => $client->getOverviewViews(),
                'pdfLogo' => $client->getPdfLogo(),
                'mainLogo' => $client->getMainLogo(),
                'mapIcon' => $client->getMapMarkerIcon(),
                'devicePageView' => $client->getDevicePageView(),
                'alarms' => $cache?->getAlarms() ?? [],
            ];
        }

        return $this->render('v2/overview/admin.html.twig', [
            'clients' => $data,
            'smtp' => $smtpRepository->findOneBy([])
        ]);
    }
}