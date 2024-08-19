<?php

namespace App\Controller\Icon\API;

use App\Repository\DeviceIconRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/api/icons/{id}/delete', name: 'app_icon_delete', methods: 'POST')]
class IconDeleteController extends AbstractController
{

    public function __invoke(int $id, DeviceIconRepository $deviceIconRepository, EntityManagerInterface $entityManager): RedirectResponse
    {
        $icon = $deviceIconRepository->find($id);

        if (!$icon) {
            return $this->redirectToRoute('app_icon_index');
        }

        $entityManager->remove($icon);
        $entityManager->flush();

        return $this->redirectToRoute('app_icon_index');
    }

}