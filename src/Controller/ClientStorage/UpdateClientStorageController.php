<?php

namespace App\Controller\ClientStorage;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/{clientId}/client-storage/{clientStorageId}', methods: ['GET', 'POST'])]
class UpdateClientStorageController extends AbstractController
{

    public function __invoke(int $clientId, int $clientStorageId): Response
    {

        return $this->render('v2/client_storage/edit.html.twig');
    }
}
