<?php

namespace App\Controller\ClientStorage;

use App\Entity\Client;
use App\Entity\User;
use App\Service\ClientStorage\ClientStorageHandler;
use App\Service\ClientStorage\Types\DeviceTypesDropdown;
use App\Service\ClientStorage\Types\DigitalEntryDropdown;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\ClientStorage;

#[Route('/admin/{clientId}/client-storage/{clientStorageId}', name: 'app_client_storage_get_post', methods: ['GET', 'POST'])]
class UpdateClientStorageController extends AbstractController
{

    public function __invoke(
        #[MapEntity(id: 'clientId')]
        Client $client,
        #[MapEntity(id: 'clientStorageId')]
        ClientStorage $clientStorage,
        Request $request,
        ClientStorageHandler $clientStorageHandler
    ): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($user->getPermission() !== 4) {
            return $this->redirectToRoute('client_overview', ['clientId' => $client->getId()]);
        }

        $deviceTypesDropdown = DeviceTypesDropdown::get($client);
        $digitalEntryDropdown = DigitalEntryDropdown::get($client);

        if ($request->isMethod('GET')) {
            return $this->render('v2/client_storage/edit.html.twig', [
                'clientStorage' => $clientStorage,
                'deviceTypesDropdown' => $deviceTypesDropdown,
                'digitalEntryDropdown' => $digitalEntryDropdown,
            ]);
        }

        $clientStorageHandler->update($clientStorage, $request);

        return $this->render('v2/client_storage/edit.html.twig', [
            'clientStorage' => $clientStorage,
            'deviceTypesDropdown' => $deviceTypesDropdown,
            'digitalEntryDropdown' => $digitalEntryDropdown,
        ]);
    }
}
