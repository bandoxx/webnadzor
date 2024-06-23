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

    public function create(Client $client, string $username, string $password, int $permission, ?int $overviewViews): User
    {
        $user = new User();

        $user->setClient($client)
            ->setPermission($permission)
            ->setUsername($username)
            ->setOverviewViews($overviewViews)
            ->addClient($client)
        ;

        $this->passwordSetter->setPassword($user, $password);

        return $user;
    }

}