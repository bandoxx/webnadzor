<?php

namespace App\Controller\LoginLog;

use App\Entity\Client;
use App\Entity\User;
use App\Repository\LoginLogRepository;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/admin/{clientId}/login-log', name: 'app_loginlog_getloginlogs', methods: 'GET')]
class LoginLogGetController extends AbstractController
{
    public function __invoke(
        #[MapEntity(id: 'clientId')]
        Client $client,
        LoginLogRepository $loginLogRepository
    ): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $logs = [];

        if ($user->isRoot()) {
            $logs = $loginLogRepository->findRootLogins();
        }

        $clientLogs = $loginLogRepository->findBy(['client' => $client], ['id' => 'DESC']);
        $logs = array_merge($logs, $clientLogs);

        usort($logs, static fn($a, $b) => strcmp($b->getServerDate()->format('U'), $a->getServerDate()->format('U')));

        return $this->render('v2/login_log/list.html.twig', [
            'logs' => $logs
        ]);
    }

}