<?php

namespace App\Service\Chart\Type\DeviceData;

use App\Entity\Device;
use App\Service\Chart\Type\BaseChartInterface;

interface DeviceDataChartInterface extends BaseChartInterface
{
    public function formatData(array $data, Device $device, ?int $entry = null): array;
}