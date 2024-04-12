<?php

namespace App\Controller\LoginLog;

use App\Repository\LoginLogArchiveRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/admin/{clientId}login-log-archive', methods: 'GET', name: 'app_loginlog_loginlogsarchive')]
class LoginLogArchiveController extends AbstractController
{

    public function __invoke($clientId, LoginLogArchiveRepository $loginLogArchiveRepository)
    {
        return $this->render('login_log/list.html.twig', [
            'logs' => $loginLogArchiveRepository->findBy(['client' => $clientId], ['id' => 'DESC'])
        ]);
    }

}