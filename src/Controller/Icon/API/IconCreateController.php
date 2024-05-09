<?php

namespace App\Controller\Icon\API;

use App\Repository\ClientRepository;
use App\Service\Image\IconUploader;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/api/client/{clientId}/icons', name: 'app_icon_new', methods: 'POST')]
class IconCreateController extends AbstractController
{

    public function __invoke(int $clientId, ClientRepository $clientRepository, Request $request, IconUploader $iconUploader): RedirectResponse
    {
        $icon = $request->files->get('icon');
        $title = $request->request->get('icon_title');
        $client = $clientRepository->find($clientId);

        if (!$icon || !$title || !$client) {
            throw new BadRequestException();
        }

        $iconUploader->uploadAndSave($icon, $client, $title);

        return $this->redirectToRoute('app_icon_index', [
            'clientId' => $clientId
        ]);
    }

}