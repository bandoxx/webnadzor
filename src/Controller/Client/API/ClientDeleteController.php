<?php

namespace App\Controller\Client\API;

use App\Repository\ClientRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/api/client/{clientId}', name: 'api_client_delete', methods: 'DELETE')]
class ClientDeleteController extends AbstractController
{

    public function __invoke(int $clientId, ClientRepository $clientRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $client = $clientRepository->find($clientId);

        if (!$client) {
            return $this->json('Not found.', Response::HTTP_NOT_FOUND);
        }

        $client->setDeleted(true);

        $entityManager->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

}