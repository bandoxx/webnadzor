<?php

namespace App\Controller\LoginLog;

use App\Entity\User;
use App\Repository\LoginLogRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/admin/{clientId}/login-log', name: 'app_loginlog_getloginlogs', methods: 'GET')]
class LoginLogGetController extends AbstractController
{
    public function __invoke(int $clientId, LoginLogRepository $loginLogRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $logs = [];

        if ($user->isRoot()) {
            $logs = $loginLogRepository->findRootLogins();
        }

        $clientLogs = $loginLogRepository->findBy(['client' => $clientId], ['id' => 'DESC']);
        $logs = array_merge($logs, $clientLogs);

        usort($logs, static fn($a, $b) => strcmp($b->getId(), $a->getId()));

        return $this->render('v2/login_log/list.html.twig', [
            'logs' => $logs
        ]);
    }

}