<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\ClientInfoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ClientInfoController extends AbstractController
{

    #[Route(path: '/info', methods: 'GET', name: 'app_clientinfo_read')]
    public function read(ClientInfoRepository $clientInfoRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $client = $clientInfoRepository->findOneBy(['client' => $user->getClient()]);

        return $this->render('client_info/list.html.twig', [
            'client' => $client
        ]);
    }

    #[Route(path: '/api/info/{id}', methods: 'PATCH', name: 'app_clientinfo_edit')]
    public function edit($id, Request $request, ClientInfoRepository $clientInfoRepository, EntityManagerInterface $entityManager): Response
    {
        $info = $clientInfoRepository->find($id);

        if (!$info) {
            return $this->json('Info not found.', Response::HTTP_NOT_FOUND);
        }

        $info->setUsername($request->request->get('username'));
        $info->setPassword($request->request->get('password'));
        $info->setHost($request->request->get('host'));

        $entityManager->flush($info);

        return $this->json(null, Response::HTTP_OK);
    }
}