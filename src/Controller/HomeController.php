<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/', name: 'app_index')]
class HomeController extends AbstractController
{
    public function __invoke(): RedirectResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user->getUserIdentifier()) {
            return $this->redirectToRoute('app_login');
        }

        if ($user->getClients()->count() > 1 || $user->getPermission() === User::ROLE_ROOT) {
            return $this->redirectToRoute('admin_overview');
        }

        return $this->redirectToRoute('client_overview', [
            'clientId' => $user->getClients()->first()->getId()
        ]);
    }
}