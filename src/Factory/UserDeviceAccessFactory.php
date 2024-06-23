<?php

namespace App\Factory;

use App\Entity\Device;
use App\Entity\User;
use App\Entity\UserDeviceAccess;

class UserDeviceAccessFactory
{

    public function create(Device $device, User $user, int $sensor): UserDeviceAccess
    {
        $access = new UserDeviceAccess();

        $access->setDevice($device)
            ->setUser($user)
            ->setSensor($sensor)
            ->setClient($device->getClient())
        ;

        return $access;
    }

}