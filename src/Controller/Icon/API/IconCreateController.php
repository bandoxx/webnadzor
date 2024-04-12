<?php

namespace App\Controller\Icon\API;

use App\Service\Image\IconUploader;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/api/client/{clientId}/icons', name: 'app_icon_new', methods: 'POST')]
class IconCreateController extends AbstractController
{

    public function __invoke($clientId, Request $request, IconUploader $iconUploader)
    {
        $icon = $request->files->get('icon');
        $title = $request->request->get('icon_title');

        if (!$icon || !$title) {
            throw new BadRequestException();
        }

        $iconUploader->uploadAndSave($icon, $this->getUser()->getClient(), $title);

        return $this->redirectToRoute('app_icon_index', [
            'clientId' => $clientId
        ]);
    }

}