<?php

namespace App\Service;

use App\Repository\DeviceAlarmRepository;
use App\Repository\LoginLogRepository;
use Doctrine\ORM\EntityManagerInterface;

class DatabaseGarbageCollector
{

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly LoginLogRepository     $loginLogRepository,
        private readonly DeviceAlarmRepository $deviceAlarmRepository
    )
    {}

    public function clean(): void
    {
        $this->cleanLoginList();
        $this->cleanAlarmList();
    }

    private function cleanAlarmList(): void
    {
        $records = $this->deviceAlarmRepository->findOlderThen(6);

        $this->remove($records);
    }

    private function cleanLoginList(): void
    {
        $records = $this->loginLogRepository->findOlderThen(6);

        $this->remove($records);
    }

    private function remove(array $records): void
    {
        $bulk = 500;
        $i = 0;
        foreach ($records as $record) {
            $this->entityManager->remove($record);
            $i++;

            if ($i % $bulk === 0) {
                $this->entityManager->flush();
            }
        }

        $this->entityManager->flush();
    }

}