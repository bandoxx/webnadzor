<?php

namespace App\Service;

use App\Factory\LoginLogFactory;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

class LoginTracker
{

    public function __construct(private LoginLogFactory $loginLogFactory, private EntityManagerInterface $entityManager, private UserRepository $userRepository)
    {}

    public function log(Request $request, bool $successfulLogin = true): void
    {
        $user = null;
        if ($username = $request->request->get('username')) {
            $user = $this->userRepository->findOneByUsername($username);
        }

        if (!$user || !$successfulLogin) {
            $log = $this->loginLogFactory->badLogin($request, $user);
            $this->entityManager->persist($log);
        } else {
            $clients = [];

            if ($user->isRoot() === false) {
                $userClients = $user->getClients()->toArray();

                foreach ($userClients as $client) {
                    if ($client->isDeleted()) {
                        continue;
                    }

                    $clients[] = $client;
                }
            }

            foreach ($clients as $client) {
                $log = $this->loginLogFactory->goodLogin($request, $user, $client);
                $this->entityManager->persist($log);
            }

            if (empty($clients)) {
                $log = $this->loginLogFactory->goodLogin($request, $user);
                $this->entityManager->persist($log);
            }
        }

        $this->entityManager->flush();
    }
}