<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\ClientFtpRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ClientFtpController extends AbstractController
{

    #[Route(path: '/ftp', methods: 'GET', name: 'app_clientftp_read')]
    public function read(ClientFtpRepository $clientFtpRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $client = $clientFtpRepository->findOneBy(['client' => $user->getClient()]);

        return $this->render('client_ftp/list.html.twig', [
            'client' => $client
        ]);
    }

    #[Route(path: '/api/ftp/{id}', methods: 'PATCH', name: 'app_clientftp_edit')]
    public function edit($id, Request $request, ClientFtpRepository $clientFtpRepository, EntityManagerInterface $entityManager): Response
    {
        $ftp = $clientFtpRepository->find($id);

        if (!$ftp) {
            return $this->json('Info not found.', Response::HTTP_NOT_FOUND);
        }

        $ftp->setUsername($request->request->get('username'));
        $ftp->setPassword($request->request->get('password'));
        $ftp->setHost($request->request->get('host'));

        $entityManager->flush();

        return $this->json(null, Response::HTTP_OK);
    }
}