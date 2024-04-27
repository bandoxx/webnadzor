<?php

namespace App\Controller\FTP\API;

use App\Repository\ClientFtpRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/api/ftp/{id}', name: 'app_clientftp_edit', methods: 'PATCH')]
class FtpEditController extends AbstractController
{
    public function __invoke(int $id, Request $request, ClientFtpRepository $clientFtpRepository, EntityManagerInterface $entityManager): JsonResponse
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