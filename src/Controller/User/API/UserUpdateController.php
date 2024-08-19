<?php

namespace App\Controller\User\API;

use App\Repository\ClientRepository;
use App\Repository\UserRepository;
use App\Service\User\UserPasswordSetter;
use App\Service\UserDeviceAccessUpdater;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/api/client/{clientId}/user/{userId}', name: 'api_user_update', methods: 'POST')]
class UserUpdateController extends AbstractController
{

    public function __invoke(int $clientId, int $userId, UserPasswordSetter $passwordSetter, Request $request, UserRepository $userRepository, ClientRepository $clientRepository, UserDeviceAccessUpdater $userDeviceAccessUpdater): RedirectResponse
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

        $permission = $request->request->get('permissions');
        if ($permission) {
            $user->setPermission($permission);
        }

        $clients = [];
        $requestClients = $request->get('clients');

        if (!$requestClients) {
            $clients[] = $clientId;
        } else {
            $clients = explode(',', $requestClients);
        }

        $userDeviceAccessUpdater->update(
            $user,
            $clients,
            explode(',', $request->get('locations'))
        );

        return $this->redirectToRoute('app_user_getusers', ['clientId' => $clientId]);

    }

}