<?php

namespace App\Controller\FTP;

use App\Repository\ClientFtpRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/admin/{clientId}/ftp', methods: 'GET', name: 'app_clientftp_read')]
class FtpReadController extends AbstractController
{

    public function __invoke($clientId, ClientFtpRepository $clientFtpRepository)
    {
        $client = $clientFtpRepository->findOneBy(['client' => $clientId]);

        return $this->render('client_ftp/list.html.twig', [
            'client' => $client
        ]);
    }

}