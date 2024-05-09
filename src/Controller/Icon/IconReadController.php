<?php

namespace App\Controller\Icon;

use App\Repository\DeviceIconRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/admin/{clientId}/icons', name: 'app_icon_index', methods: 'GET')]
class IconReadController extends AbstractController
{

    public function __invoke(int $clientId, DeviceIconRepository $deviceIconRepository, ParameterBagInterface $parameterBag): Response
    {
        $icons = $deviceIconRepository->findBy(['client' => $clientId]);

        foreach ($icons as $icon) {
            $icon->setFullPath(sprintf("%s/%s", $parameterBag->get('icon_directory'), $icon->getFilename()));
        }

        return $this->render('icon/index.html.twig', [
            'icons' => $icons
        ]);
    }

}