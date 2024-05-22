<?php

namespace App\Factory;

use Symfony\Component\Lock\LockInterface;
use Symfony\Component\Lock\Store\SemaphoreStore;

class LockFactory
{
    public function create(string $name): LockInterface
    {
        $store = new SemaphoreStore();

        return (new \Symfony\Component\Lock\LockFactory($store))->createLock($name);
    }

}