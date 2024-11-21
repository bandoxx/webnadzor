<?php

namespace App\Service\Client;

use App\Entity\Client;
use App\Factory\ClientFtpFactory;
use App\Factory\ClientSettingFactory;
use App\Repository\UserRepository;
use App\Service\Image\ClientImage\MainLogoHandler;
use App\Service\Image\ClientImage\MapMarkerIconHandler;
use App\Service\Image\ClientImage\PdfLogoHandler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

class ClientUpdater
{

    public function __construct(
        private readonly MainLogoHandler        $mainLogoHandler,
        private readonly MapMarkerIconHandler   $mapMarkerIconHandler,
        private readonly PdfLogoHandler         $pdfLogoHandler,
        private readonly EntityManagerInterface $entityManager,
        private readonly ClientSettingFactory   $clientSettingFactory,
        private readonly UserRepository         $userRepository,
        private readonly ClientFtpFactory       $clientFtpFactory
    ) {}

    # TODO: Replace Request object with parameters or DTO
    public function updateByRequest(Request $request, Client $client): void
    {
        if ($overview = $request->request->get('overview_view')) {
            $client->setOverviewViews($overview);
        }

        if ($deviceOverview = $request->request->get('device_overview_view')) {
            $client->setDevicePageView($deviceOverview);
        }

        $client->setName($request->request->get('name'));
        $client->setAddress($request->request->get('address'));
        $client->setOIB($request->request->get('oib'));

        $this->entityManager->flush();

        if (!$client->getId()) {
            $clientSettings = $this->clientSettingFactory->create($client);
            $clientFtp = $this->clientFtpFactory->create($client);

            $this->entityManager->persist($client);
            $this->entityManager->persist($clientSettings);
            $this->entityManager->persist($clientFtp);
            $this->entityManager->flush();

            $users = $this->userRepository->findRootUsers();
            foreach ($users as $user) {
                $user->addClient($client);
            }

            $this->entityManager->flush();
        }

        if ($mainLogo = $request->files->get('main_logo')) {
            $fileName = $this->mainLogoHandler->upload($mainLogo, $client);
            $this->mainLogoHandler->save($client, $fileName);
        }

        if ($pdfLogo = $request->files->get('pdf_logo')) {
            $fileName = $this->pdfLogoHandler->upload($pdfLogo, $client);
            $this->pdfLogoHandler->save($client, $fileName);
        }

        if ($mapIcon = $request->files->get('map_marker_icon')) {
            $fileName = $this->mapMarkerIconHandler->upload($mapIcon, $client);
            $this->mapMarkerIconHandler->save($client, $fileName);
        }
    }
}