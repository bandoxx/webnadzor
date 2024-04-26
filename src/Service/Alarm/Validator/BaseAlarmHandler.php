<?php

namespace App\Service\Alarm\Validator;

use App\Entity\Device;
use App\Entity\DeviceAlarm;
use App\Entity\DeviceData;
use App\Factory\DeviceAlarmFactory;
use App\Repository\DeviceAlarmRepository;
use Doctrine\ORM\EntityManagerInterface;

class BaseAlarmHandler
{

    protected bool $alarmShouldBeOn;

    public function __construct(public DeviceAlarmFactory $alarmFactory, public DeviceAlarmRepository $deviceAlarmRepository, public EntityManagerInterface $entityManager)
    {
        $this->alarmShouldBeOn = false;
    }

    public function findAlarm(Device $device, $type): ?DeviceAlarm
    {
        return $this->deviceAlarmRepository->findActiveAlarm($device, $type);
    }

    public function finish(?DeviceAlarm $alarm, DeviceData $deviceData, $type, $sensor = null): void
    {
        if (!($alarm xor $this->alarmShouldBeOn)) {
            return;
        }

        if ($alarm && !$this->alarmShouldBeOn) {
            $this->closeAlarm($alarm, $deviceData);
        } elseif (!$alarm && $this->alarmShouldBeOn) {
            $this->createAlarm($deviceData, $type, $sensor);
        }
    }

    public function closeAlarm(DeviceAlarm $alarm, DeviceData $deviceData): void
    {
        $alarm->setEndServerDate(new \DateTime());
        $alarm->setEndDeviceDate($deviceData->getDeviceDate());

        $this->entityManager->flush();
    }

    public function createAlarm(DeviceData $deviceData, $type, $sensor = null): void
    {
        $alarm = $this->alarmFactory->create($deviceData, $type, $sensor);
        $this->entityManager->persist($alarm);

        $this->entityManager->flush();
    }
}