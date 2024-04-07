<?php

namespace App\Command\DatabaseMigration;

use App\Entity\Client;
use App\Entity\Device;
use App\Entity\DeviceAlarm;
use App\Entity\DeviceData;
use App\Entity\LoginLog;
use App\Entity\User;
use App\Entity\UserDeviceAccess;
use App\Factory\DeviceDataEntryFactory;
use App\Factory\UserDeviceAccessFactory;
use Doctrine\ORM\EntityManagerInterface;

class UserDataImport
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserDeviceAccessFactory $userDeviceAccessFactory
    ) {}

    public function import(\PDO $pdo, Client $client): void
    {
        $users = $pdo->query('SELECT * FROM `config_users`')->fetchAll(\PDO::FETCH_OBJ);

        foreach ($users as $userData) {
            $user = new User();

            $user->setClient($client)
                ->setPassword($userData->password)
                ->setUsername($userData->username)
                ->setPermission($userData->permissions)
                ->setFromOldSystem(true)
                ->setOldId($userData->id)
            ;

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $query = sprintf("SELECT * FROM `config_permissions` WHERE user_id = %d", $userData->id);

            $permissions = $pdo->query($query)->fetchAll(\PDO::FETCH_OBJ);

            foreach ($permissions as $permissionData) {
                $this->migrateUserDeviceAccess($user, $permissionData);
            }
        }

        //$this->migrateLoginLogs($pdo, $client);
    }

    private function migrateLoginLogs(\PDO $pdo, Client $client)
    {
        $logs = $pdo->query('SELECT * FROM `login_log`')->fetchAll(\PDO::FETCH_OBJ);

        foreach ($logs as $logData) {
            $log = new LoginLog();

            $user = $this->entityManager->getRepository(User::class)->findOneBy(['oldId' => $logData->user_id]);

            $log->setHost($logData->host)
                ->setUsername($logData->username)
                //->setStatus($logData->statu1q s)
                ->setUser($user)
                ->setClient($client)
                ->setServerDate(new \DateTime($logData->server_date))
                ->setUserAgent($logData->user_agent)
                ->setOs($logData->os ?? null)
                ->setBrowser($logData->browser ?? null)
                ->setPassword(null)
            ;

            $this->entityManager->persist($log);
        }

        $this->entityManager->flush();
    }

    private function migrateUserDeviceAccess($user, $permissionData)
    {
        dd($permissionData);
        $device = $this->entityManager->getRepository(Device::class)->findOneBy(['oldId' => $permissionData->ldevice_id]);
        if (!$permissionData->sensor) {
            for ($i = 1; $i <= 2; $i++) {
                $permission = $this->userDeviceAccessFactory->create($device, $user, $i);

                $this->entityManager->persist($permission);
                $this->entityManager->flush();
            }
        } else {
            $permission = $this->userDeviceAccessFactory->create($device, $user, preg_replace("/[^0-9]/", "", $permissionData->sensor));

            $this->entityManager->persist($permission);
            $this->entityManager->flush();
        }
    }
}