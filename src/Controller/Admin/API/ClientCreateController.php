<?php

namespace App\Controller\Admin\API;

use App\Entity\Client;
use App\Repository\ClientRepository;
use App\Service\Image\LogoUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/api/client', name: 'api_client_create', methods: 'POST')]
class ClientCreateController extends AbstractController
{

    public function __invoke(Request $request, LogoUploader $logoUploader, ClientRepository $clientRepository, EntityManagerInterface $entityManager): RedirectResponse
    {
        $client = new Client();
        $client->setName($request->request->get('name'));
        $client->setOverviewViews($request->request->getInt('overview_view'));

        $entityManager->persist($client);
        $entityManager->flush();

        if ($mainLogo = $request->files->get('main_logo')) {
            $logoUploader->uploadAndSaveMainLogo($mainLogo, $client);
        }

        if ($pdfLogo = $request->files->get('pdf_logo')) {
            $logoUploader->uploadAndSavePDFLogo($pdfLogo, $client);
        }

        if ($mapIcon = $request->files->get('map_marker_icon')) {
            $logoUploader->uploadAndSaveMapMarkerIcon($mapIcon, $client);
        }

        $entityManager->flush();

        return $this->redirectToRoute('admin_overview');
    }

}