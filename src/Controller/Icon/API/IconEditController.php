<?php

namespace App\Controller\Icon\API;

use App\Entity\DeviceIcon;
use App\Repository\DeviceIconRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/api/icons/{id}', name: 'app_icon_edit', methods: 'POST')]
class IconEditController extends AbstractController
{

    public function __invoke(int $id, Request $request, DeviceIconRepository $deviceIconRepository, EntityManagerInterface $entityManager): RedirectResponse
    {
        /** @var DeviceIcon $icon */
        $icon = $deviceIconRepository->find($id);

        $icon->setTitle($request->request->get('title'));

        $entityManager->flush();

        return $this->redirectToRoute('app_icon_index');
    }

}