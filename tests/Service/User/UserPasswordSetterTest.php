<?php

namespace App\Tests\Service\User;

use App\Entity\User;
use App\Service\User\UserPasswordSetter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserPasswordSetterTest extends TestCase
{
    private UserPasswordHasherInterface&MockObject $passwordHasher;
    private UserPasswordSetter $passwordSetter;

    protected function setUp(): void
    {
        $this->passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $this->passwordSetter = new UserPasswordSetter($this->passwordHasher);
    }

    public function testSetPasswordHashesAndSetsPassword(): void
    {
        $user = $this->createMock(User::class);
        $plainPassword = 'mySecurePassword123';
        $hashedPassword = '$2y$13$hashedPasswordExample';

        $this->passwordHasher
            ->expects($this->once())
            ->method('hashPassword')
            ->with($user, $plainPassword)
            ->willReturn($hashedPassword);

        $user
            ->expects($this->once())
            ->method('setPassword')
            ->with($hashedPassword);

        $this->passwordSetter->setPassword($user, $plainPassword);
    }

    public function testSetPasswordWithEmptyPassword(): void
    {
        $user = $this->createMock(User::class);
        $emptyPassword = '';
        $hashedEmpty = '$2y$13$hashedEmptyPassword';

        $this->passwordHasher
            ->expects($this->once())
            ->method('hashPassword')
            ->with($user, $emptyPassword)
            ->willReturn($hashedEmpty);

        $user
            ->expects($this->once())
            ->method('setPassword')
            ->with($hashedEmpty);

        $this->passwordSetter->setPassword($user, $emptyPassword);
    }

    public function testSetPasswordWithSpecialCharacters(): void
    {
        $user = $this->createMock(User::class);
        $specialPassword = 'P@$$w0rd!čćžšđ<>&"\'';
        $hashedSpecial = '$2y$13$hashedSpecialPassword';

        $this->passwordHasher
            ->expects($this->once())
            ->method('hashPassword')
            ->with($user, $specialPassword)
            ->willReturn($hashedSpecial);

        $user
            ->expects($this->once())
            ->method('setPassword')
            ->with($hashedSpecial);

        $this->passwordSetter->setPassword($user, $specialPassword);
    }

    public function testSetPasswordWithVeryLongPassword(): void
    {
        $user = $this->createMock(User::class);
        $longPassword = str_repeat('a', 1000);
        $hashedLong = '$2y$13$hashedLongPassword';

        $this->passwordHasher
            ->expects($this->once())
            ->method('hashPassword')
            ->with($user, $longPassword)
            ->willReturn($hashedLong);

        $user
            ->expects($this->once())
            ->method('setPassword')
            ->with($hashedLong);

        $this->passwordSetter->setPassword($user, $longPassword);
    }
}
