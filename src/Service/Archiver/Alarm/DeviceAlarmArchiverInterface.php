<?php

namespace App\Service\Archiver\Alarm;

use App\Entity\Device;
use App\Service\Archiver\ArchiverInterface;

interface DeviceAlarmArchiverInterface extends ArchiverInterface
{
    public function generate(Device $device, array $data): void;
}