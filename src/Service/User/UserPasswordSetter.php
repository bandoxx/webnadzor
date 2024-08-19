<?php

namespace App\Service\User;

use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserPasswordSetter
{

    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {}

    public function setPassword(User $user, string $password): void
    {
        $user->setPassword($this->passwordHasher->hashPassword($user, $password));
    }
}