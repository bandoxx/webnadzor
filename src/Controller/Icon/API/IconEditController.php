<?php

namespace App\Controller\Icon\API;

use App\Entity\DeviceIcon;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/api/icons/{id}', name: 'app_icon_edit', methods: 'POST')]
class IconEditController extends AbstractController
{

    public function __invoke(
        #[MapEntity(id: 'id')]
        DeviceIcon $icon,
        Request $request,
        EntityManagerInterface $entityManager
    ): RedirectResponse
    {
        $icon->setTitle($request->request->get('title'));

        $entityManager->flush();

        return $this->redirectToRoute('app_icon_index');
    }

}