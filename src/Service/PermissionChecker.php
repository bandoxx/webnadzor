<?php

namespace App\Service;

use App\Entity\Client;
use App\Entity\User;

class PermissionChecker
{

    public static function isValid(User $user, Client $client): bool
    {
        if ($user->getPermission() === User::ROLE_ROOT) {
            return true;
        }

        if ($user->getClients()->contains($client)) {
            return true;
        }

        return false;
    }
}