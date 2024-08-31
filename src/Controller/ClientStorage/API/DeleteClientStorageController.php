<?php

namespace App\Controller\ClientStorage\API;

use App\Entity\Client;
use App\Entity\ClientStorage;
use App\Service\ClientStorage\ClientStorageHandler;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/{clientId}/client-storage/{clientStorageId}', name: 'api_client_storage_delete', methods: ['DELETE'])]
class DeleteClientStorageController extends AbstractController
{
    public function __invoke(
        #[MapEntity(id: 'clientId')]
        Client $client,
        #[MapEntity(id: 'clientStorageId')]
        ClientStorage $clientStorage,
        ClientStorageHandler $clientStorageHandler
    ): Response
    {
        $clientStorageHandler->removeClientStorage($clientStorage);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
