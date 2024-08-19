<?php

namespace App\Controller\Admin\API;

use App\Repository\ClientRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/api/client/{clientId}/delete', name: 'api_client_delete', methods: 'POST')]
class ClientDeleteController extends AbstractController
{

    public function __invoke(int $clientId, ClientRepository $clientRepository, EntityManagerInterface $entityManager): RedirectResponse
    {
        $client = $clientRepository->find($clientId);

        if (!$client) {
            return $this->redirectToRoute('admin_overview');
        }

        $client->setDeleted(true);
        $client->setDeletedByUser($this->getUser());
        $client->setDeletedAt(new \DateTime());

        $entityManager->flush();

        $devices = $client->getDevice()->toArray();

        foreach ($devices as $device) {
            $device->setDeleted(true);
        }

        $entityManager->flush();

        return $this->redirectToRoute('admin_overview');
    }

}