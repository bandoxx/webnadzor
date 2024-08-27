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

#[Route('/admin/{id}/client-storage/{client_storage_id}', methods: ['GET', 'POST'])]
class UpdateClientStorageController extends AbstractController
{

    public function __invoke(
        #[MapEntity(id: 'id')]
        Client $client,
        #[MapEntity(id: 'client_storage_id')]
        ClientStorage $clientStorage,
        Request $request,
        ClientStorageHandler $clientStorageHandler
    ): Response
    {
        $dropDown = $clientStorageHandler->getDropDown($client);

        if ($request->isMethod('GET')) {
            return $this->render('v2/client_storage/edit.html.twig', [
                'clientStorage' => $clientStorage,
                'dropdown' => $dropDown
            ]);
        }

        $clientStorageHandler->update($clientStorage, $request);

        return $this->render('v2/client_storage/edit.html.twig', [
            'clientStorage' => $clientStorage,
            'dropdown' => $dropDown
        ]);
    }
}
