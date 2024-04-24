<?php

namespace App\Controller\User\API;

use App\Repository\UserRepository;
use App\Service\User\UserRemover;
use App\Service\UserDeviceAccessUpdater;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/api/user/{userId}', name: 'api_user_delete', methods: 'DELETE')]
class UserRemoveController extends AbstractController
{

    public function __invoke($userId, UserRepository $userRepository, UserRemover $userRemover): JsonResponse
    {
        $user = $userRepository->find($userId);

        if (!$user) {
            return $this->json('Not found', Response::HTTP_NOT_FOUND);
        }

        $userRemover->remove($user);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}