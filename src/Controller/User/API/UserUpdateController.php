<?php

namespace App\Controller\User\API;

use App\Repository\UserRepository;
use App\Service\UserDeviceAccessUpdater;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/admin/{clientId}/user/{userId}', name: 'api_user_update', methods: 'PATCH')]
class UserUpdateController extends AbstractController
{

    public function __invoke($clientId, $userId, Request $request, UserRepository $userRepository, UserDeviceAccessUpdater $userDeviceAccessUpdater): JsonResponse
    {
        $user = $userRepository->find($userId);

        $userDeviceAccessUpdater->update(
            $user,
            $request->get('locations'),
            $request->request->get('permissions')
        );

        return $this->json(true, Response::HTTP_ACCEPTED);

    }

}