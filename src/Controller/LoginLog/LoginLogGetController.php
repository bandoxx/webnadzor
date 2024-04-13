<?php

namespace App\Controller\LoginLog;

use App\Repository\LoginLogRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/admin/{clientId}/login-log', methods: 'GET', name: 'app_loginlog_getloginlogs')]
class LoginLogGetController extends AbstractController
{
    public function __invoke($clientId, LoginLogRepository $loginLogRepository)
    {

        return $this->render('login_log/list.html.twig', [
            'logs' => $loginLogRepository->findBy(['client' => $clientId], ['id' => 'DESC'])
        ]);
    }

}