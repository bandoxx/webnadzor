<?php

namespace App\Controller\Icon;

use App\Repository\DeviceIconRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/admin/{clientId}/icons', name: 'app_icon_index', methods: 'GET')]
class IconReadController extends AbstractController
{

    public function __invoke($clientId, DeviceIconRepository $deviceIconRepository, ParameterBagInterface $parameterBag)
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