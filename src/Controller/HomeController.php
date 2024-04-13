<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;

#[Route(path: '/', name: 'app_index')]
class HomeController extends AbstractController
{
    public function __invoke(UserInterface $user): RedirectResponse
    {
        if (!$user->getUserIdentifier()) {
            return $this->redirectToRoute('app_login');
        }

        if ($user->getPermission() === 4) {
            return $this->redirectToRoute('admin_overview');
        }

        return $this->redirectToRoute('client_overview', [
            'clientId' => $user->getClient()->getId()
        ]);
    }
}