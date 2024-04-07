<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\LoginLogArchiveRepository;
use App\Repository\LoginLogRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class LoginLogController extends AbstractController
{

    #[Route(path: '/login-log', methods: 'GET', name: 'app_loginlog_getloginlogs')]
    public function getLoginLogs(LoginLogRepository $loginLogRepository)
    {
        $client = $this->getUser()->getClient();

        return $this->render('login_log/list.html.twig', [
            'logs' => $loginLogRepository->findBy(['client' => $client], ['id' => 'DESC'])
        ]);
    }

    #[Route(path: '/login-log-archive', methods: 'GET', name: 'app_loginlog_loginlogsarchive')]
    public function loginLogsArchive(LoginLogArchiveRepository $loginLogArchiveRepository)
    {
        $client = $this->getUser()->getClient();

        return $this->render('login_log/list.html.twig', [
            'logs' => $loginLogArchiveRepository->findBy(['client' => $client], ['id' => 'DESC'])
        ]);
    }
}