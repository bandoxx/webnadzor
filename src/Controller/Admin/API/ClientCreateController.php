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

        $mainLogo = $request->files->get('main_logo');

        if ($mainLogo) {
            $logoUploader->uploadAndSaveMainLogo($mainLogo, $client);
        }

        $pdfLogo = $request->files->get('pdf_logo');

        if ($pdfLogo) {
            $logoUploader->uploadAndSavePDFLogo($pdfLogo, $client);
        }

        return $this->redirectToRoute('admin_overview');
    }

}