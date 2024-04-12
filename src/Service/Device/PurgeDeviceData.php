<?php

namespace App\Service\Device;

use App\Repository\DeviceAlarmRepository;
use App\Repository\DeviceDataRepository;
use App\Repository\UserDeviceAccessRepository;

class PurgeDeviceData
{

    public function __construct(
        private readonly DeviceAlarmRepository $deviceAlarmRepository,
        private readonly DeviceDataRepository  $deviceDataRepository,
        private readonly UserDeviceAccessRepository $deviceAccessRepository
    )
    {

    }

    public function removeAllDataRelatedToDevice(int $deviceId): void
    {
        $this->deviceAlarmRepository->deleteAlarmsRelatedToDevice($deviceId);
        $this->deviceAccessRepository->deleteAccessesRelatedToDevice($deviceId);

        $this->removeDeviceData($deviceId);
    }

    public function removeDeviceData(int $deviceId): void
    {
        $this->deviceDataRepository->removeDataForDevice($deviceId);
    }
}