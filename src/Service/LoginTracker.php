<?php

namespace App\Service;

use App\Factory\LoginLogFactory;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Request;

class LoginTracker
{

    public function __construct(private LoginLogFactory $loginLogFactory, private EntityManagerInterface $entityManager, private UserRepository $userRepository)
    {
    }

    public function log(Request $request, bool $successLogin): void
    {
        $user = $this->userRepository->findOneByUsername($request->request->get('username'));

        if (!$user) {
            throw new BadRequestException("User not found");
        }

        $log = $this->loginLogFactory->create($request, $user);

        $this->entityManager->persist($log);
        $this->entityManager->flush();
    }

}