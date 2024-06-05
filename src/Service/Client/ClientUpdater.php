<?php

namespace App\Service\Client;

use App\Entity\Client;
use App\Factory\ClientSettingFactory;
use App\Service\Image\LogoUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

class ClientUpdater
{

    public function __construct(private LogoUploader $logoUploader, private EntityManagerInterface $entityManager, private ClientSettingFactory $clientSettingFactory) {}

    public function updateByRequest(Request $request, Client $client): void
    {
        if ($request->request->get('overview_view')) {
            $client->setOverviewViews($request->request->get('overview_view'));
        }

        if ($request->request->get('device_overview_view')) {
            $client->setDevicePageView($request->request->get('device_overview_view'));
        }

        $client->setName($request->request->get('name'));
        $client->setAddress($request->request->get('address'));
        $client->setOIB($request->request->get('oib'));

        if (!$client->getId()) {
            $clientSettings = $this->clientSettingFactory->create($client);

            $this->entityManager->persist($client);
            $this->entityManager->persist($clientSettings);
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