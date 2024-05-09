<?php

namespace App\Controller\User;

use App\Repository\UserRepository;
use App\Service\DeviceLocationHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/admin/{clientId}/users', name: 'app_user_getusers', methods: 'GET')]
class UsersGetController extends AbstractController
{

    public function __invoke(int $clientId, UserRepository $userRepository, DeviceLocationHandler $deviceLocationHandler): Response
    {
        $users = $userRepository->findUsersByClientAndSuperAdmin($clientId);

        foreach ($users as $user) {
            $deviceLocations = $deviceLocationHandler->getUserDeviceLocations($user);
            $user->setAvailableLocations($deviceLocations);
        }

        return $this->render('user/list.html.twig', [
            'users' => $users,
            'client_locations' => $deviceLocationHandler->getClientDeviceLocations($clientId),
            'client_id' => $clientId
        ]);
    }
}