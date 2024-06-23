<?php

namespace App\Controller\User\API;

use App\Repository\ClientRepository;
use App\Repository\UserRepository;
use App\Service\User\UserPasswordSetter;
use App\Service\UserDeviceAccessUpdater;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/admin/user/{userId}', name: 'api_user_update', methods: 'PATCH')]
class UserUpdateController extends AbstractController
{

    public function __invoke(int $userId, UserPasswordSetter $passwordSetter, Request $request, UserRepository $userRepository, ClientRepository $clientRepository, UserDeviceAccessUpdater $userDeviceAccessUpdater): JsonResponse
    {
        $user = $userRepository->find($userId);

        if (!$user) {
            throw new BadRequestHttpException('User doesnt exist');
        }

        $password = $request->request->get('password');
        $passwordConfirm = $request->request->get('password_again');

        if ($password !== null && $passwordConfirm !== null && $password === $passwordConfirm) {
            $passwordSetter->setPassword($user, $password);
        }

        $overviewViews = $request->request->get('overview_views');

        $user->setOverviewViews(is_numeric($overviewViews) ? $overviewViews : null);

        if ($permission = $request->request->get('permissions')) {
            $user->setPermission($request->request->get($permission));
        }

        $clients = $request->get('clients');

        $user->getClients()->clear();

        foreach ($clients as $clientId) {
            $client = $clientRepository->find($clientId);

            if (!$client) {
                continue;
            }

            $user->addClient($client);
        }

        $userDeviceAccessUpdater->update(
            $user,
            $request->get('locations')
        );

        return $this->json(true, Response::HTTP_ACCEPTED);

    }

}