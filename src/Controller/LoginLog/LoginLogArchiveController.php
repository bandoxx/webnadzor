<?php

namespace App\Controller\LoginLog;

use App\Repository\LoginLogArchiveRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/admin/{clientId}/login-log-archive', name: 'app_loginlog_loginlogsarchive', methods: 'GET')]
class LoginLogArchiveController extends AbstractController
{

    public function __invoke(int $clientId, LoginLogArchiveRepository $loginLogArchiveRepository): Response
    {
        return $this->render('login_log/archive.html.twig', [
            'logs' => $loginLogArchiveRepository->findBy(['client' => $clientId], ['id' => 'DESC'])
        ]);
    }

}