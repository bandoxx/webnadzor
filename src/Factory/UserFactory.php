<?php

namespace App\Factory;

use App\Entity\Client;
use App\Entity\User;
use App\Service\User\UserPasswordSetter;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFactory
{

    public function __construct(
        private readonly UserPasswordSetter $passwordSetter
    )
    {}

    public function create(Client $client, $username, $password, $permission)
    {
        $user = new User();

        $user->setClient($client)
            ->setPermission($permission)
            ->setUsername($username)
        ;

        $this->passwordSetter->setPassword($user, $password);

        return $user;
    }

}