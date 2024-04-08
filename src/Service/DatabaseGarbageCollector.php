<?php

namespace App\Service;

use App\Repository\LoginLogRepository;
use Doctrine\ORM\EntityManagerInterface;

class DatabaseGarbageCollector
{

    public function __construct(
        private EntityManagerInterface $entityManager,
        private LoginLogRepository $loginLogRepository
    )
    {

    }

    public function cleanLoginList(): void
    {
        $records = $this->loginLogRepository->findOlderThen6Months();
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