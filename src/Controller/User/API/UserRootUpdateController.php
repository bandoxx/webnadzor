<?php

namespace App\Controller\User\API;

use App\Entity\User;
use App\Service\User\UserPasswordSetter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/api/user/{id}', name: 'api_user_root_update', methods: 'PATCH')]
class UserRootUpdateController extends AbstractController
{

    public function __invoke(User $user, UserPasswordSetter $passwordSetter, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $password = $request->request->get('password');
        $passwordConfirm = $request->request->get('password_again');

        if ($password !== null && $passwordConfirm !== null && $password !== $passwordConfirm) {
            return $this->json(['error_message' => 'Zaporke se ne podudaraju.']);
        }

        $passwordSetter->setPassword($user, $password);

        $entityManager->flush();

        return $this->json(true, Response::HTTP_ACCEPTED);

    }

}