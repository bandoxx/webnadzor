<?php

namespace App\Factory;

use App\Entity\Client;
use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFactory
{

    public function __construct(
        private UserPasswordHasherInterface $passwordHasher
    )
    {}

    public function create(Client $client, $username, $password, $permission)
    {
        $user = new User();

        $user->setClient($client)
            ->setPermission($permission)
            ->setUsername($username)
        ;

        $user->setPassword($this->passwordHasher->hashPassword($user, $password));

        return $user;
    }

}