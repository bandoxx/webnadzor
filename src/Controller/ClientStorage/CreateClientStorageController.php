<?php

namespace App\Controller\ClientStorage;

use App\Entity\Client;
use App\Service\ClientStorage\ClientStorageHandler;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\ClientStorage;

#[Route('/admin/{clientId}/client-storage', name: 'app_client_storage_post', methods: ['GET', 'POST'])]
class CreateClientStorageController extends AbstractController
{

    public function __invoke(
        #[MapEntity(id: 'clientId')]
        Client $client,
        Request $request,
        ClientStorageHandler $clientStorageHandler
    ): Response
    {
        $dropDown = $clientStorageHandler->getDropDown($client);
        $clientStorage = (new ClientStorage())->setClient($client);

        if ($request->isMethod('GET')) {
            return $this->render('v2/client_storage/edit.html.twig', [
                'dropdown' => $dropDown,
                'clientStorage' => $clientStorage,
            ]);
        }

        $clientStorageHandler->update($clientStorage, $request);

        return $this->redirectToRoute('app_client_storage_get_post', [
            'clientId' => $client->getId(),
            'clientStorageId' => $clientStorage->getId()
        ]);
    }
}
