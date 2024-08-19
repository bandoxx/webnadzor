<?php

namespace App\Controller\User\API;

use App\Repository\UserRepository;
use App\Service\User\UserRemover;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/api/user/{userId}', name: 'api_user_delete', methods: 'POST')]
class UserRemoveController extends AbstractController
{

    public function __invoke(int $userId, Request $request, UserRepository $userRepository, UserRemover $userRemover): RedirectResponse
    {
        $user = $userRepository->find($userId);

        $redirectUrl = $request->headers->get('referer');

        if (!$user) {
            // TODO: Flashbag message
            return $this->redirect($redirectUrl);
        }

        $userRemover->remove($user);

        return $this->redirect($redirectUrl);
    }
}