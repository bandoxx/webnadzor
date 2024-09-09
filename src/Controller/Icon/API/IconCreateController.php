<?php

namespace App\Controller\Icon\API;

use App\Repository\ClientRepository;
use App\Service\Image\DeviceImageHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/api/icons', name: 'app_icon_new', methods: 'POST')]
class IconCreateController extends AbstractController
{

    public function __invoke(ClientRepository $clientRepository, Request $request, DeviceImageHandler $deviceImageHandler): RedirectResponse
    {
        $icon = $request->files->get('icon');
        $title = $request->request->get('icon_title');

        if (!$icon || !$title) {
            throw new BadRequestException();
        }

        $fileName = $deviceImageHandler->upload($icon);
        $deviceImageHandler->save($fileName, $title);

        return $this->redirectToRoute('app_icon_index');
    }

}