<?php

namespace App\Controller\Icon;

use App\Repository\DeviceIconRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/admin/{clientId}/icons', name: 'app_icon_index', methods: 'GET')]
class IconReadController extends AbstractController
{

    public function __invoke(DeviceIconRepository $deviceIconRepository, ParameterBagInterface $parameterBag)
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

}