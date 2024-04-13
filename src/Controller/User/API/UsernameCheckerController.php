<?php

namespace App\Controller\User\API;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/api/username_checker', name: 'api_username_checker', methods: "GET")]
class UsernameCheckerController extends AbstractController
{

    public function __invoke(Request $request, UserRepository $userRepository): JsonResponse
    {
        $username = $request->get('fieldValue');

        if (!$username) {
            return $this->json('Bad request.', Response::HTTP_BAD_REQUEST);
        }

        $user = $userRepository->findOneByUsername($username);

        if ($user) {
            return $this->json(null, Response::HTTP_BAD_REQUEST);
        }

        return $this->json(null, Response::HTTP_OK);
    }

}