<?php

namespace App\Controller\FTP\API;

use App\Entity\ClientFtp;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/api/ftp/{id}', name: 'app_clientftp_edit', methods: 'PATCH')]
class FtpEditController extends AbstractController
{
    public function __invoke(
        #[MapEntity(id: 'id')]
        ClientFtp $ftp,
        Request $request,
        EntityManagerInterface $entityManager
    ): JsonResponse
    {
        $ftp->setUsername($request->request->get('username'));
        $ftp->setPassword($request->request->get('password'));
        $ftp->setHost($request->request->get('host'));

        $entityManager->flush();

        return $this->json(null, Response::HTTP_OK);
    }
}