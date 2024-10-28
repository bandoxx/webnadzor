<?php

namespace App\Service\Alarm\Types;

use App\Entity\DeviceData;

interface AlarmTypeInterface
{
    public function getType(): string;
    public function getMessage(DeviceData $deviceData, ?int $sensor);
    public function getShortMessage(DeviceData $deviceData, ?int $sensor);
}