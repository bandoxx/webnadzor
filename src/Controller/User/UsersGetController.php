<?php

namespace App\Controller\User;

use App\Repository\ClientRepository;
use App\Repository\UserRepository;
use App\Service\DeviceLocationHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/admin/{clientId}/users', name: 'app_user_getusers', methods: 'GET')]
class UsersGetController extends AbstractController
{

    public function __invoke(int $clientId, UserRepository $userRepository, ClientRepository $clientRepository, DeviceLocationHandler $deviceLocationHandler): Response
    {
        $users = $userRepository->findUsersByClientAndSuperAdmin($clientId);

        foreach ($users as $user) {
            $deviceLocations = $deviceLocationHandler->getUserDeviceLocations($user);
            $user->setAvailableLocations($deviceLocations);
        }

        $clients = $clientRepository->findAllActive();

        return $this->render('v2/user/list.html.twig', [
            'users' => $users,
            'clients' => $clients,
            'client_locations' => $deviceLocationHandler->getClientDeviceLocations($clientId, true),
            'client_id' => $clientId
        ]);
    }
}