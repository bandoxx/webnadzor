<?php

namespace App\Controller\Icon\API;

use App\Entity\DeviceIcon;
use App\Repository\DeviceIconRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/api/icons/{id}', name: 'app_icon_edit', methods: 'PATCH')]
class IconEditController extends AbstractController
{

    public function __invoke(int $id, Request $request, DeviceIconRepository $deviceIconRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        /** @var DeviceIcon $icon */
        $icon = $deviceIconRepository->find($id);

        $icon->setTitle($request->request->get('title'));

        $entityManager->flush();

        return $this->json(null, Response::HTTP_ACCEPTED);
    }

}