<?php

namespace App\Service\Chart\Type\Device;

use App\Service\Chart\Type\BaseChartInterface;

interface DeviceChartInterface extends BaseChartInterface
{
    public function formatData(array $data): array;
}