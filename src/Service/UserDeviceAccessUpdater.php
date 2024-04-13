<?php

namespace App\Service;

use App\Entity\User;
use App\Factory\UserDeviceAccessFactory;
use App\Repository\DeviceRepository;
use Doctrine\ORM\EntityManagerInterface;

class UserDeviceAccessUpdater
{

    public function __construct(
        private DeviceRepository $deviceRepository,
        private UserDeviceAccessFactory $userDeviceAccessFactory,
        private EntityManagerInterface $entityManager
    ) {}

    public function update(User $user, $locations, $permission)
    {
        $accesses = $user->getUserDeviceAccesses()->toArray();

        foreach ($accesses as $access) {
            $this->entityManager->remove($access);
        }

        $this->entityManager->flush();


        if ($permission == 1 || empty($permission)) {
            if (!$locations) {
                return;
            }

            foreach ($locations as $location) {
                [$deviceId, $sensor] = explode('-', $location);
                $device = $this->deviceRepository->find($deviceId);

                $userDeviceAccess = $this->userDeviceAccessFactory->create($device, $user, $sensor);

                $this->entityManager->persist($userDeviceAccess);
                $this->entityManager->flush();
            }
        }
    }

}