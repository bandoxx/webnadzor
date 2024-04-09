<?php

namespace App\Controller;

use App\Entity\DeviceIcon;
use App\Repository\DeviceIconRepository;
use App\Service\Image\IconUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class IconController extends AbstractController
{
    #[Route(path: '/icons', name: 'app_icon_index', methods: 'GET')]
    public function index(DeviceIconRepository $deviceIconRepository, ParameterBagInterface $parameterBag): Response
    {
        $client = $this->getUser()->getClient();

        $icons = $deviceIconRepository->findBy(['client' => $client]);

        foreach ($icons as $icon) {
            $icon->setFullPath(sprintf("%s/%s", $parameterBag->get('icon_directory'), $icon->getFilename()));
        }

        return $this->render('icon/index.html.twig', [
            'icons' => $icons
        ]);
    }

    #[Route(path: '/api/icons/{id}', name: 'app_icon_edit', methods: 'PATCH')]
    public function edit($id, Request $request, DeviceIconRepository $deviceIconRepository, EntityManagerInterface $entityManager): Response
    {
        /** @var DeviceIcon $icon */
        $icon = $deviceIconRepository->find($id);

        $icon->setTitle($request->request->get('title'));

        $entityManager->flush();

        return $this->json(null, Response::HTTP_ACCEPTED);
    }

    #[Route(path: '/api/icons/{id}', name: 'app_icon_delete', methods: 'DELETE')]
    public function delete($id, DeviceIconRepository $deviceIconRepository, EntityManagerInterface $entityManager)
    {
        $icon = $deviceIconRepository->find($id);

        if (!$icon) {
            return $this->json('Icon not found', Response::HTTP_NOT_FOUND);
        }

        $entityManager->remove($icon);
        $entityManager->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    #[Route(path: '/api/icons', name: 'app_icon_new', methods: 'POST')]
    public function new(Request $request, IconUploader $iconUploader): Response
    {
        $icon = $request->files->get('icon');
        $title = $request->request->get('icon_title');

        if (!$icon || !$title) {
            throw new BadRequestException();
        }

        $iconUploader->uploadAndSave($icon, $this->getUser()->getClient(), $title);

        return new RedirectResponse($this->generateUrl('app_icon_index'));
    }
}
