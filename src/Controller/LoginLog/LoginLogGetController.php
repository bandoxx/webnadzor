<?php

namespace App\Controller\LoginLog;

use App\Repository\LoginLogRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/admin/{clientId}/login-log', name: 'app_loginlog_getloginlogs', methods: 'GET')]
class LoginLogGetController extends AbstractController
{
    public function __invoke(int $clientId, LoginLogRepository $loginLogRepository): Response
    {
        return $this->render('v2/login_log/list.html.twig', [
            'logs' => $loginLogRepository->findBy(['client' => $clientId], ['id' => 'DESC'])
        ]);
    }

}