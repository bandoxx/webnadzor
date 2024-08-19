<?php

namespace App\Service\Client;

use App\Entity\Client;
use App\Factory\ClientFtpFactory;
use App\Factory\ClientSettingFactory;
use App\Repository\UserRepository;
use App\Service\Image\LogoUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

class ClientUpdater
{

    public function __construct(
        private readonly LogoUploader $logoUploader,
        private readonly EntityManagerInterface $entityManager,
        private readonly ClientSettingFactory $clientSettingFactory,
        private readonly UserRepository $userRepository,
        private readonly ClientFtpFactory $clientFtpFactory
    ) {}

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

        if (!$client->getId()) {
            $clientSettings = $this->clientSettingFactory->create($client);
            $clientFtp = $this->clientFtpFactory->create($client);

            $this->entityManager->persist($client);
            $this->entityManager->persist($clientSettings);
            $this->entityManager->persist($clientFtp);
            $this->entityManager->flush();

            $users = $this->userRepository->findBy(['permission' => 4]);
            foreach ($users as $user) {
                $user->addClient($client);
            }

            $this->entityManager->flush();
        }

        if ($mainLogo = $request->files->get('main_logo')) {
            $this->logoUploader->uploadAndSaveMainLogo($mainLogo, $client);
        }

        if ($pdfLogo = $request->files->get('pdf_logo')) {
            $this->logoUploader->uploadAndSavePDFLogo($pdfLogo, $client);
        }

        if ($mapIcon = $request->files->get('map_marker_icon')) {
            $this->logoUploader->uploadAndSaveMapMarkerIcon($mapIcon, $client);
        }

        $this->entityManager->flush();
    }
}