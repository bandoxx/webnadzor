<?php

namespace App\Controller\LoginLog\API;

use App\Repository\ClientRepository;
use App\Repository\LoginLogArchiveRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;

#[Route(path: '/api/{clientId}/archive/daily', name: 'api_login_log_archive_daily', methods: 'GET')]
class LoginLogTableController extends AbstractController
{

    public function __invoke(int $clientId, RouterInterface $router, ClientRepository $clientRepository, LoginLogArchiveRepository $loginLogArchiveRepository): JsonResponse
    {
        $archiveData = $loginLogArchiveRepository->findBy(['client' => $clientId]);
        $result = [];

        $i = 0;
        foreach ($archiveData as $data) {
            $result[] = [
                ++$i,
                $data->getArchiveDate()->format('d.m.Y.'),
                $data->getServerDate()->format('d.m.Y. H:i:s'),
                $data->getFilename(),
                sprintf('<div style="height: 3px;">&nbsp;</div><div class="actionbar"><a href="%s.pdf" class="action view"><span>PDF</span></a></div>', $router->generate('api_login_log_archive_download', [
                    'id' => $data->getId()
                ]))
            ];
        }

        return $this->json([
            'data' => $result
        ], Response::HTTP_OK);

    }

}