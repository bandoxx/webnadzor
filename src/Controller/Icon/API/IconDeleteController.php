<?php

namespace App\Controller\Icon\API;

use App\Entity\DeviceIcon;
use App\Repository\DeviceIconRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/api/icons/{id}', name: 'app_icon_delete', methods: 'DELETE')]
class IconDeleteController extends AbstractController
{

    public function __invoke($id, DeviceIconRepository $deviceIconRepository, EntityManagerInterface $entityManager)
    {
        $icon = $deviceIconRepository->find($id);

        if (!$icon) {
            return $this->json('Icon not found', Response::HTTP_NOT_FOUND);
        }

        $entityManager->remove($icon);
        $entityManager->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

}