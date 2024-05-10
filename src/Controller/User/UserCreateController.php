<?php

namespace App\Controller\User;

use App\Factory\UserFactory;
use App\Repository\ClientRepository;
use App\Repository\UserRepository;
use App\Service\UserDeviceAccessUpdater;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/admin/{clientId}/users', name: 'app_user_createuser', methods: 'POST')]
class UserCreateController extends AbstractController
{

    public function __invoke(int $clientId, Request $request, EntityManagerInterface $entityManager, UserRepository $userRepository, UserFactory $userFactory, UserDeviceAccessUpdater $userDeviceAccessUpdater, ClientRepository $clientRepository): JsonResponse
    {
        $permission = $request->request->get('permissions');

        $client = $clientRepository->find($clientId);

        if (!$client) {
            throw new BadRequestHttpException('Client doesnt exist');
        }

        $username = $request->request->get('username');

        $user = $userRepository->findOneByUsername($username);

        if ($user) {
            return $this->json(null, Response::HTTP_BAD_REQUEST);
        }

        $overviewViews = $request->request->get('overview_views');

        $user = $userFactory->create(
            $client,
            $username,
            $request->request->get('password'),
            $permission,
            is_numeric($overviewViews) ? $overviewViews : null
        );

        $entityManager->persist($user);
        $entityManager->flush();

        $userDeviceAccessUpdater->update(
            $user,
            $request->get('locations')
        );

        return $this->json(true, Response::HTTP_CREATED);
    }

}