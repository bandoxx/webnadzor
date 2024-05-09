<?php

namespace App\Controller\User\API;

use App\Repository\UserRepository;
use App\Service\User\UserPasswordSetter;
use App\Service\UserDeviceAccessUpdater;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/admin/{clientId}/user/{userId}', name: 'api_user_update', methods: 'PATCH')]
class UserUpdateController extends AbstractController
{

    public function __invoke(int $clientId, int $userId, UserPasswordSetter $passwordSetter, Request $request, UserRepository $userRepository, UserDeviceAccessUpdater $userDeviceAccessUpdater): JsonResponse
    {
        $user = $userRepository->find($userId);

        $password = $request->request->get('password');
        $passwordConfirm = $request->request->get('password_again');

        if ($password !== null && $passwordConfirm !== null && $password === $passwordConfirm) {
            $passwordSetter->setPassword($user, $password);
        }

        $userDeviceAccessUpdater->update(
            $user,
            $request->get('locations'),
            $request->request->get('permissions')
        );

        return $this->json(true, Response::HTTP_ACCEPTED);

    }

}