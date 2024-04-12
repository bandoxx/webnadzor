<?php

namespace App\Controller\User;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\DeviceLocationHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/admin/{clientId}/users', methods: 'GET', name: 'app_user_getusers')]
class UsersGetController extends AbstractController
{

    public function __invoke($clientId, UserRepository $userRepository, DeviceLocationHandler $deviceLocationHandler): Response
    {
        $users = $userRepository->findUsersByClient($clientId);

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