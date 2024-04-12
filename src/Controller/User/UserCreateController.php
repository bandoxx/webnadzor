<?php

namespace App\Controller\User;

use App\Factory\UserDeviceAccessFactory;
use App\Factory\UserFactory;
use App\Repository\ClientRepository;
use App\Repository\DeviceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/admin/{clientId}/users', methods: 'POST', name: 'app_user_createuser')]
class UserCreateController extends AbstractController
{

    public function __invoke($clientId, Request $request, EntityManagerInterface $entityManager, DeviceRepository $deviceRepository, UserFactory $userFactory, UserDeviceAccessFactory $userDeviceAccessFactory, ClientRepository $clientRepository): JsonResponse
    {
        $permission = $request->request->get('permissions');

        $client = $clientRepository->find($clientId);

        $user = $userFactory->create(
            $client,
            $request->request->get('username'),
            $request->request->get('password'),
            $permission
        );

        $entityManager->persist($user);
        $entityManager->flush();

        if ($permission == 1) {
            $locations = explode(',', $request->request->get('locations'));

            foreach ($locations as $location) {
                [$deviceId, $sensor] = explode('-', $location);
                $device = $deviceRepository->find($deviceId);

                $userDeviceAccess = $userDeviceAccessFactory->create($device, $user, $sensor);

                $entityManager->persist($userDeviceAccess);
                $entityManager->flush();
            }
        }

        return $this->json(true, Response::HTTP_CREATED);

    }

}