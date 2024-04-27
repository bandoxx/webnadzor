<?php

namespace App\Controller\Admin\API;

use App\Repository\ClientRepository;
use App\Service\Image\LogoUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/api/client/{clientId}', name: 'api_client_update', methods: 'POST')]
class ClientUpdateController extends AbstractController
{

    public function __invoke(int $clientId, Request $request, LogoUploader $logoUploader, ClientRepository $clientRepository, EntityManagerInterface $entityManager): RedirectResponse|\Symfony\Component\HttpFoundation\JsonResponse
    {
        $client = $clientRepository->find($clientId);
        if (!$client) {
            return $this->json(null, Response::HTTP_BAD_REQUEST);
        }

        if ($mainLogo = $request->files->get('main_logo')) {
            $logoUploader->uploadAndSaveMainLogo($mainLogo, $client);
        }

        if ($pdfLogo = $request->files->get('pdf_logo')) {
            $logoUploader->uploadAndSavePDFLogo($pdfLogo, $client);
        }

        if ($mapIcon = $request->files->get('map_marker_icon')) {
            $logoUploader->uploadAndSaveMapMarkerIcon($mapIcon, $client);
        }

        $client->setOverviewViews($request->request->getInt('overview_view'));
        $client->setName($request->request->get('name'));

        $entityManager->flush();

        return $this->redirectToRoute('admin_overview');
    }

}