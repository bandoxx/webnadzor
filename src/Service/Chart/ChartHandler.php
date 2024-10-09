<?php

namespace App\Service\Chart;

use App\Entity\Device;
use App\Repository\DeviceDataRepository;
use App\Service\Chart\Type\Device\DeviceChartInterface;
use App\Service\Chart\Type\DeviceData\DeviceDataChartInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class ChartHandler
{

    public function __construct(
        private readonly DeviceDataRepository $deviceDataRepository,
        private readonly iterable $deviceTypes,
        private readonly iterable $deviceDataTypes
    ) {}

    /**
     * Meant to be used when we want to fetch battery/signal/vbat or any other information related to device, not particular entry
     */
    public function createDeviceChart(Device $device, string $type, ?int $limit = 20): array
    {
        /** @var DeviceChartInterface $chartType */
        foreach ($this->deviceTypes as $chartType) {
            if ($chartType->getType() === $type) {
                $data = $this->getDeviceDataset($device->getId(), $limit);

                return $chartType->formatData($data);
            }
        }

        throw new BadRequestHttpException(sprintf("Type for chart is not valid, provided: %s", $type));
    }

    /**
     * Meant to be used when we want to fetch any temperature/humidity related information which is related to particular entry
     */
    public function createDeviceDataChart(Device $device, string $type, ?int $entry = null, ?\DateTime $fromDate = null, ?\DateTime $toDate = null): array
    {
        /** @var DeviceDataChartInterface $chartType */
        foreach ($this->deviceDataTypes as $chartType) {
            if ($chartType->getType() === $type) {
                $data = $this->getDeviceDataDataset($device->getId(), $fromDate, $toDate);

                return $chartType->formatData($data, $device, $entry);
            }
        }

        throw new BadRequestHttpException(sprintf("Type for chart is not valid, provided: %s", $type));
    }

    private function getDeviceDataset(int $deviceId, ?int $limit = 20)
    {
        $cacheKey = sprintf("chart_dataset_%s", $deviceId);

        return (new FilesystemAdapter('', 60))
            ->get($cacheKey, function() use ($deviceId, $limit) {
                return $this->deviceDataRepository->getDeviceChartData(
                    $deviceId, $limit
                );
            }
        );
    }

    private function getDeviceDataDataset(int $deviceId, ?\DateTime $fromDate = null, ?\DateTime $toDate = null): array
    {
        $fromKey = ($fromDate) ? $fromDate->getTimestamp() : 'null';
        $toKey   = ($toDate) ? $toDate->getTimestamp() : 'null';

        $cacheKey = sprintf("chart_dataset_%s_%s_%s", $deviceId, $fromKey, $toKey);

        return (new FilesystemAdapter('', 60))
            ->get($cacheKey, function() use ($deviceId, $fromDate, $toDate) {
                return $this->deviceDataRepository->getChartData(
                    $deviceId,
                    $fromDate,
                    $toDate
                );
            }
        );
    }
}