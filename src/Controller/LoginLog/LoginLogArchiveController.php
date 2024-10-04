<?php

namespace App\Controller\LoginLog;

use App\Entity\Client;
use App\Repository\LoginLogArchiveRepository;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route(path: '/admin/{clientId}/login-log-archive', name: 'app_loginlog_loginlogsarchive', methods: 'GET')]
class LoginLogArchiveController extends AbstractController
{

    public function __invoke(
        #[MapEntity(id: 'clientId')]
        Client $client,
        LoginLogArchiveRepository $loginLogArchiveRepository,
        UrlGeneratorInterface $router
    ): Response
    {
        $archiveData = $loginLogArchiveRepository->findBy(['client' => $client]);
        $result = [];
        $i = 0;

        foreach ($archiveData as $data) {
            $result[] = [
                'id' => ++$i,
                'archive_date' => $data->getArchiveDate()->format('d.m.Y.'),
                'server_date' => $data->getServerDate()->format('d.m.Y. H:i:s'),
                'filename' => $data->getFilename(),
                'path' => sprintf("%s", $router->generate('api_login_log_archive_download', [
                    'id' => $data->getId()
                ], UrlGeneratorInterface::ABSOLUTE_URL)),
            ];
        }

        return $this->render('v2/login_log/archive.html.twig', [
            'logs' => $result,
        ]);
    }

}