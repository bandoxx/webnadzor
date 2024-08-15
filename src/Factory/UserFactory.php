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

    public function create(string $username, string $password, int $permission, ?int $overviewViews): User
    {
        $user = new User();

        $user
            ->setPermission($permission)
            ->setUsername($username)
            ->setOverviewViews($overviewViews)
        ;

        $this->passwordSetter->setPassword($user, $password);

        return $user;
    }

}