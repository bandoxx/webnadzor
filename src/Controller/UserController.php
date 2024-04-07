<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\UserDeviceAccess;
use App\Factory\UserDeviceAccessFactory;
use App\Factory\UserFactory;
use App\Repository\DeviceRepository;
use App\Repository\UserDeviceAccessRepository;
use App\Repository\UserRepository;
use App\Service\DeviceLocationHandler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class UserController extends AbstractController
{

    #[Route(path: '/users', methods: 'POST', name: 'app_user_createuser')]
    public function createUser(Request $request, EntityManagerInterface $entityManager, DeviceRepository $deviceRepository, UserFactory $userFactory, UserDeviceAccessFactory $userDeviceAccessFactory)
    {
        $currentUser = $this->getUser();

        $user = $userFactory->create(
            $currentUser->getClient(),
            $request->request->get('username'),
            $request->request->get('password'),
            $request->request->get('permissions')
        );

        $entityManager->persist($user);
        $entityManager->flush();

        $locations = explode(',', $request->request->get('locations'));

        foreach ($locations as $location) {
            [$deviceId, $sensor] = explode('-', $location);
            $device = $deviceRepository->find($deviceId);

            $userDeviceAccess = $userDeviceAccessFactory->create($device, $user, $sensor);

            $entityManager->persist($userDeviceAccess);
            $entityManager->flush();
        }

        return $this->json(true, Response::HTTP_CREATED);
    }

    #[Route(path: '/users', methods: 'GET', name: 'app_user_getusers')]
    public function getUsers(UserRepository $userRepository, DeviceLocationHandler $deviceLocationHandler)
    {
        /** @var User $user */
        $user = $this->getUser();
        $client = $user->getClient();

        $users = $userRepository->findUsersByClient($client);

        foreach ($users as $user) {
            $deviceLocations = $deviceLocationHandler->getUserDeviceLocations($user);
            $user->setAvailableLocations($deviceLocations);
        }

        return $this->render('user/list.html.twig', [
            'users' => $userRepository->findUsersByClient($client),
            'client_locations' => $deviceLocationHandler->getClientDeviceLocations($client)
        ]);
    }

}