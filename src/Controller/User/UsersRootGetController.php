<?php

namespace App\Controller\User;

use App\Repository\ClientRepository;
use App\Repository\UserRepository;
use App\Service\DeviceLocationHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/admin/users', name: 'app_admin_get_root_users', methods: 'GET')]
class UsersRootGetController extends AbstractController
{

    public function __invoke(UserRepository $userRepository, ClientRepository $clientRepository, DeviceLocationHandler $deviceLocationHandler): Response
    {
        $users = $userRepository->findRootUsers();

        return $this->render('v2/user/root_list.html.twig', [
            'users' => $users
        ]);
    }
}