<?php

namespace App\Controller\User;

use App\Repository\ClientRepository;
use App\Repository\UserRepository;
use App\Service\DeviceLocationHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/admin/users', name: 'app_admin_getRootUsers', methods: 'GET')]
class UsersRootGetController extends AbstractController
{

    public function __invoke(UserRepository $userRepository, ClientRepository $clientRepository, DeviceLocationHandler $deviceLocationHandler): Response
    {
        $users = $userRepository->findUsersByClientAndSuperAdmin(127); //need to create back-end for this

        foreach ($users as $user) {
            $deviceLocations = $deviceLocationHandler->getUserDeviceLocations($user);
            $user->setAvailableLocations($deviceLocations);
        }

        $clients = $clientRepository->findAllActive();

        return $this->render('v2/user/rootList.html.twig', [
            'users' => $users,
            'clients' => $clients,
            'client_locations' => $deviceLocationHandler->getClientDeviceLocations(127, true),
            'client_id' => 127
        ]);
    }
}