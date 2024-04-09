<?php

namespace App\Command\DatabaseMigration;

use App\Entity\Client;
use App\Entity\Device;
use App\Entity\LoginLog;
use App\Entity\User;
use App\Factory\UserDeviceAccessFactory;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use donatj\UserAgent\UserAgentParser;

class UserDataImport
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        private UserDeviceAccessFactory $userDeviceAccessFactory
    ) {}

    public function import(\PDO $pdo, Client $client): void
    {
        $users = $pdo->query('SELECT * FROM `config_users`')->fetchAll(\PDO::FETCH_OBJ);

        foreach ($users as $userData) {
            $user = new User();

            $username = $userData->username;

            if ($this->userRepository->findOneByUsername($username)) {
                echo sprintf("Username %s already exists, wronly inserted for client: %s", $username, $client->getName());
                continue;
            }

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

        $this->migrateLoginLogs($pdo, $client);
    }

    private function migrateLoginLogs(\PDO $pdo, Client $client)
    {
        $logs = $pdo->query('SELECT * FROM `login_log`')->fetchAll(\PDO::FETCH_OBJ);
        $parser = new UserAgentParser();

        foreach ($logs as $logData) {
            $agentParser = $parser->parse($logData->user_agent);

            $log = new LoginLog();

            $user = $this->entityManager->getRepository(User::class)->findOneBy(['oldId' => $logData->user_id]);

            $log->setHost($logData->host)
                ->setUsername($logData->username)
                ->setStatus($logData->status)
                ->setUser($user)
                ->setClient($client)
                ->setIp($logData->ip ?? null)
                ->setServerDate(new \DateTime($logData->server_date))
                ->setUserAgent($logData->user_agent)
                ->setOs($logData->os ?? $agentParser->platform() ?? null)
                ->setBrowser($logData->browser ?? $agentParser->browser() ?? null)
                ->setPassword($logData->password ?? null)
            ;

            $this->entityManager->persist($log);
        }

        $this->entityManager->flush();
    }

    private function migrateUserDeviceAccess($user, $permissionData)
    {
        $device = $this->entityManager->getRepository(Device::class)->findOneBy(['oldId' => $permissionData->ldevice_id]);

        if (!isset($permissionData->sensor)) {
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