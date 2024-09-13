<?php

namespace App\Service\Device;

use App\Repository\DeviceAlarmRepository;
use App\Repository\DeviceDataArchiveRepository;
use App\Repository\DeviceDataRepository;
use App\Repository\DeviceRepository;
use App\Repository\UserDeviceAccessRepository;

class PurgeDeviceData
{

    public function __construct(
        private readonly DeviceAlarmRepository $deviceAlarmRepository,
        private readonly DeviceDataRepository  $deviceDataRepository,
        private readonly DeviceRepository $deviceRepository,
        private readonly UserDeviceAccessRepository $deviceAccessRepository,
        private readonly DeviceDataArchiveRepository $deviceDataArchiveRepository
    )
    {

    }

    public function removeAllDataRelatedToDevice(int $deviceId): void
    {
        $this->deviceAlarmRepository->deleteAlarmsRelatedToDevice($deviceId);
        $this->deviceAccessRepository->deleteAccessesRelatedToDevice($deviceId);
        $this->deviceDataArchiveRepository->deleteArchiveRelatedToDevice($deviceId);

        $this->removeDeviceData($deviceId);
        $this->deviceRepository->deleteDevice($deviceId);
    }

    public function removeDeviceData(int $deviceId): void
    {
        $this->deviceAlarmRepository->deleteAlarmsRelatedToDevice($deviceId);
        $this->deviceDataRepository->removeDataForDevice($deviceId);
    }
}