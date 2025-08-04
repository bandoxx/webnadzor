<?php

namespace App\Service\Alarm\Validator;

use App\Entity\Device;
use App\Entity\DeviceAlarm;
use App\Entity\DeviceData;
use App\Factory\DeviceAlarmFactory;
use App\Repository\DeviceAlarmRepository;
use App\Service\Alarm\Types\AlarmTypeInterface;
use Doctrine\ORM\EntityManagerInterface;

class BaseAlarmHandler
{
    public function __construct(protected DeviceAlarmFactory $alarmFactory, protected DeviceAlarmRepository $deviceAlarmRepository, protected EntityManagerInterface $entityManager)
    {}

    protected function findAlarm(Device $device, string $type, ?int $sensor = null, ?string $message = null): ?DeviceAlarm
    {
        return $this->deviceAlarmRepository->findActiveAlarm($device, $type, $sensor, $message);
    }
    
    protected function findDuplicateAlarm(DeviceData $deviceData, AlarmTypeInterface $alarmType, ?int $sensor = null): ?DeviceAlarm
    {
        $message = $alarmType->getMessage($deviceData, $sensor);
        return $this->findAlarm($deviceData->getDevice(), $alarmType->getType(), $sensor, $message);
    }

    protected function closeAlarm(DeviceData $deviceData, AlarmTypeInterface $alarmType, ?int $sensor = null): void
    {
        $alarm = $this->findAlarm($deviceData->getDevice(), $alarmType->getType(), $sensor);

        if (!$alarm) {
            return;
        }

        $alarm->setEndServerDate(new \DateTime());
        $alarm->setEndDeviceDate($deviceData->getDeviceDate());

        $this->entityManager->flush();
    }

    protected function createAlarm(DeviceData $deviceData, AlarmTypeInterface $alarmType, ?int $sensor = null): void
    {
        // Check if an active alarm with the same type, device, sensor, and message already exists
        $alarm = $this->findDuplicateAlarm($deviceData, $alarmType, $sensor);

        if ($alarm) {
            return;
        }

        $alarm = $this->alarmFactory->create($deviceData, $alarmType, $sensor);
        $this->entityManager->persist($alarm);
        $this->entityManager->flush();
    }
}