<?php

namespace App\Controller\Icon\API;

use App\Entity\DeviceIcon;
use App\Repository\DeviceIconRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/api/icons/{id}/delete', name: 'app_icon_delete', methods: 'POST')]
class IconDeleteController extends AbstractController
{

    public function __invoke(
        #[MapEntity(id: 'id')]
        DeviceIcon $icon,
        DeviceIconRepository $deviceIconRepository,
        EntityManagerInterface $entityManager
    ): RedirectResponse
    {
        $entityManager->remove($icon);
        $entityManager->flush();

        return $this->redirectToRoute('app_icon_index');
    }

}