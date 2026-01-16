<?php

namespace App\Tests\Service\Chart;

use App\Entity\Device;
use App\Entity\DeviceData;
use App\Repository\DeviceDataChartRepository;
use App\Service\Chart\ChartHandler;
use App\Service\Chart\Type\Device\DeviceChartInterface;
use App\Service\Chart\Type\DeviceData\DeviceDataChartInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class ChartHandlerTest extends TestCase
{
    private DeviceDataChartRepository&MockObject $repository;
    private Device&MockObject $device;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(DeviceDataChartRepository::class);
        $this->device = $this->createMock(Device::class);
        $this->device->method('getId')->willReturn(1);

        // Clear filesystem cache before each test
        $cacheDir = sys_get_temp_dir() . '/symfony-cache';
        if (is_dir($cacheDir)) {
            $this->clearDirectory($cacheDir);
        }
    }

    private function clearDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($files as $file) {
            if ($file->isDir()) {
                @rmdir($file->getRealPath());
            } else {
                @unlink($file->getRealPath());
            }
        }
    }

    private function createHandler(array $deviceTypes = [], array $deviceDataTypes = []): ChartHandler
    {
        return new ChartHandler(
            $this->repository,
            $deviceTypes,
            $deviceDataTypes
        );
    }

    // ==========================================
    // Tests for createDeviceChart()
    // ==========================================

    public function testCreateDeviceChartReturnsFormattedData(): void
    {
        $expectedData = [['date' => '2024-01-01', 'value' => 100]];
        $formattedResult = ['labels' => ['2024-01-01'], 'data' => [100]];

        $this->repository->method('getDeviceChartData')
            ->with(1, 20)
            ->willReturn([$this->createMock(DeviceData::class)]);

        $chartType = $this->createMock(DeviceChartInterface::class);
        $chartType->method('getType')->willReturn('battery');
        $chartType->method('formatData')->willReturn($formattedResult);

        $handler = $this->createHandler([$chartType]);
        $result = $handler->createDeviceChart($this->device, 'battery');

        $this->assertIsArray($result);
        $this->assertEquals($formattedResult, $result);
    }

    public function testCreateDeviceChartWithCustomLimit(): void
    {
        $this->repository->expects($this->once())
            ->method('getDeviceChartData')
            ->with(1, 50)
            ->willReturn([]);

        $chartType = $this->createMock(DeviceChartInterface::class);
        $chartType->method('getType')->willReturn('signal');
        $chartType->method('formatData')->willReturn([]);

        $handler = $this->createHandler([$chartType]);
        $handler->createDeviceChart($this->device, 'signal', 50);
    }

    public function testCreateDeviceChartThrowsExceptionForInvalidType(): void
    {
        $chartType = $this->createMock(DeviceChartInterface::class);
        $chartType->method('getType')->willReturn('battery');

        $handler = $this->createHandler([$chartType]);

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Type for chart is not valid, provided: invalid_type');

        $handler->createDeviceChart($this->device, 'invalid_type');
    }

    public function testCreateDeviceChartThrowsExceptionWithEmptyTypes(): void
    {
        $handler = $this->createHandler([], []);

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Type for chart is not valid, provided: battery');

        $handler->createDeviceChart($this->device, 'battery');
    }

    public function testCreateDeviceChartSelectsCorrectTypeFromMultiple(): void
    {
        $batteryType = $this->createMock(DeviceChartInterface::class);
        $batteryType->method('getType')->willReturn('battery');
        $batteryType->method('formatData')->willReturn(['type' => 'battery']);

        $signalType = $this->createMock(DeviceChartInterface::class);
        $signalType->method('getType')->willReturn('signal');
        $signalType->method('formatData')->willReturn(['type' => 'signal']);

        $this->repository->method('getDeviceChartData')->willReturn([]);

        $handler = $this->createHandler([$batteryType, $signalType]);

        $result = $handler->createDeviceChart($this->device, 'signal');
        $this->assertEquals(['type' => 'signal'], $result);
    }

    public function testCreateDeviceChartUsesDefaultLimit(): void
    {
        $this->repository->expects($this->once())
            ->method('getDeviceChartData')
            ->with(1, 20)
            ->willReturn([]);

        $chartType = $this->createMock(DeviceChartInterface::class);
        $chartType->method('getType')->willReturn('battery');
        $chartType->method('formatData')->willReturn([]);

        $handler = $this->createHandler([$chartType]);
        $handler->createDeviceChart($this->device, 'battery');
    }

    // ==========================================
    // Tests for createDeviceDataChart()
    // ==========================================

    public function testCreateDeviceDataChartReturnsFormattedData(): void
    {
        $formattedResult = ['labels' => ['2024-01-01'], 'datasets' => [[1, 2, 3]]];

        $this->repository->method('getChartData')
            ->with(1, null, null)
            ->willReturn([]);

        $chartType = $this->createMock(DeviceDataChartInterface::class);
        $chartType->method('getType')->willReturn('temperature');
        $chartType->method('formatData')->willReturn($formattedResult);

        $handler = $this->createHandler([], [$chartType]);
        $result = $handler->createDeviceDataChart($this->device, 'temperature');

        $this->assertIsArray($result);
        $this->assertEquals($formattedResult, $result);
    }

    public function testCreateDeviceDataChartWithDateRange(): void
    {
        $fromDate = new \DateTime('2024-01-01');
        $toDate = new \DateTime('2024-01-31');

        $this->repository->expects($this->once())
            ->method('getChartData')
            ->with(1, $fromDate, $toDate)
            ->willReturn([]);

        $chartType = $this->createMock(DeviceDataChartInterface::class);
        $chartType->method('getType')->willReturn('temperature');
        $chartType->method('formatData')->willReturn([]);

        $handler = $this->createHandler([], [$chartType]);
        $handler->createDeviceDataChart($this->device, 'temperature', null, $fromDate, $toDate);
    }

    public function testCreateDeviceDataChartWithEntry(): void
    {
        $entry = 2;

        $chartType = $this->createMock(DeviceDataChartInterface::class);
        $chartType->method('getType')->willReturn('humidity');
        $chartType->expects($this->once())
            ->method('formatData')
            ->with($this->anything(), $this->device, $entry)
            ->willReturn(['entry' => $entry]);

        $this->repository->method('getChartData')->willReturn([]);

        $handler = $this->createHandler([], [$chartType]);
        $result = $handler->createDeviceDataChart($this->device, 'humidity', $entry);

        $this->assertEquals(['entry' => $entry], $result);
    }

    public function testCreateDeviceDataChartThrowsExceptionForInvalidType(): void
    {
        $chartType = $this->createMock(DeviceDataChartInterface::class);
        $chartType->method('getType')->willReturn('temperature');

        $handler = $this->createHandler([], [$chartType]);

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Type for chart is not valid, provided: invalid_type');

        $handler->createDeviceDataChart($this->device, 'invalid_type');
    }

    public function testCreateDeviceDataChartThrowsExceptionWithEmptyTypes(): void
    {
        $handler = $this->createHandler([], []);

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Type for chart is not valid, provided: temperature');

        $handler->createDeviceDataChart($this->device, 'temperature');
    }

    public function testCreateDeviceDataChartSelectsCorrectTypeFromMultiple(): void
    {
        $temperatureType = $this->createMock(DeviceDataChartInterface::class);
        $temperatureType->method('getType')->willReturn('temperature');
        $temperatureType->method('formatData')->willReturn(['type' => 'temperature']);

        $humidityType = $this->createMock(DeviceDataChartInterface::class);
        $humidityType->method('getType')->willReturn('humidity');
        $humidityType->method('formatData')->willReturn(['type' => 'humidity']);

        $this->repository->method('getChartData')->willReturn([]);

        $handler = $this->createHandler([], [$temperatureType, $humidityType]);

        $result = $handler->createDeviceDataChart($this->device, 'humidity');
        $this->assertEquals(['type' => 'humidity'], $result);
    }

    public function testCreateDeviceDataChartWithNullEntry(): void
    {
        $chartType = $this->createMock(DeviceDataChartInterface::class);
        $chartType->method('getType')->willReturn('temperature');
        $chartType->expects($this->once())
            ->method('formatData')
            ->with($this->anything(), $this->device, null)
            ->willReturn([]);

        $this->repository->method('getChartData')->willReturn([]);

        $handler = $this->createHandler([], [$chartType]);
        $handler->createDeviceDataChart($this->device, 'temperature', null);
    }

    public function testCreateDeviceDataChartWithOnlyFromDate(): void
    {
        $fromDate = new \DateTime('2024-01-01');

        $this->repository->expects($this->once())
            ->method('getChartData')
            ->with(1, $fromDate, null)
            ->willReturn([]);

        $chartType = $this->createMock(DeviceDataChartInterface::class);
        $chartType->method('getType')->willReturn('temperature');
        $chartType->method('formatData')->willReturn([]);

        $handler = $this->createHandler([], [$chartType]);
        $handler->createDeviceDataChart($this->device, 'temperature', null, $fromDate, null);
    }

    public function testCreateDeviceDataChartWithOnlyToDate(): void
    {
        $toDate = new \DateTime('2024-01-31');

        $this->repository->expects($this->once())
            ->method('getChartData')
            ->with(1, null, $toDate)
            ->willReturn([]);

        $chartType = $this->createMock(DeviceDataChartInterface::class);
        $chartType->method('getType')->willReturn('temperature');
        $chartType->method('formatData')->willReturn([]);

        $handler = $this->createHandler([], [$chartType]);
        $handler->createDeviceDataChart($this->device, 'temperature', null, null, $toDate);
    }

    // ==========================================
    // Edge Case Tests
    // ==========================================

    public function testCreateDeviceChartWithEmptyDataFromRepository(): void
    {
        $this->repository->method('getDeviceChartData')->willReturn([]);

        $chartType = $this->createMock(DeviceChartInterface::class);
        $chartType->method('getType')->willReturn('battery');
        $chartType->expects($this->once())
            ->method('formatData')
            ->with([])
            ->willReturn(['labels' => [], 'data' => []]);

        $handler = $this->createHandler([$chartType]);
        $result = $handler->createDeviceChart($this->device, 'battery');

        $this->assertEquals(['labels' => [], 'data' => []], $result);
    }

    public function testCreateDeviceDataChartWithEmptyDataFromRepository(): void
    {
        $this->repository->method('getChartData')->willReturn([]);

        $chartType = $this->createMock(DeviceDataChartInterface::class);
        $chartType->method('getType')->willReturn('temperature');
        $chartType->expects($this->once())
            ->method('formatData')
            ->with([], $this->device, null)
            ->willReturn(['labels' => [], 'datasets' => []]);

        $handler = $this->createHandler([], [$chartType]);
        $result = $handler->createDeviceDataChart($this->device, 'temperature');

        $this->assertEquals(['labels' => [], 'datasets' => []], $result);
    }

    public function testCreateDeviceChartWithZeroLimit(): void
    {
        $this->repository->expects($this->once())
            ->method('getDeviceChartData')
            ->with(1, 0)
            ->willReturn([]);

        $chartType = $this->createMock(DeviceChartInterface::class);
        $chartType->method('getType')->willReturn('battery');
        $chartType->method('formatData')->willReturn([]);

        $handler = $this->createHandler([$chartType]);
        $handler->createDeviceChart($this->device, 'battery', 0);
    }

    public function testCreateDeviceChartWithNegativeLimit(): void
    {
        $this->repository->expects($this->once())
            ->method('getDeviceChartData')
            ->with(1, -1)
            ->willReturn([]);

        $chartType = $this->createMock(DeviceChartInterface::class);
        $chartType->method('getType')->willReturn('battery');
        $chartType->method('formatData')->willReturn([]);

        $handler = $this->createHandler([$chartType]);
        $handler->createDeviceChart($this->device, 'battery', -1);
    }

    public function testCreateDeviceDataChartWithSameDates(): void
    {
        $date = new \DateTime('2024-01-15');
        $sameDate = clone $date;

        $this->repository->expects($this->once())
            ->method('getChartData')
            ->with(1, $date, $sameDate)
            ->willReturn([]);

        $chartType = $this->createMock(DeviceDataChartInterface::class);
        $chartType->method('getType')->willReturn('temperature');
        $chartType->method('formatData')->willReturn([]);

        $handler = $this->createHandler([], [$chartType]);
        $handler->createDeviceDataChart($this->device, 'temperature', null, $date, $sameDate);
    }

    // ==========================================
    // Type Iteration Tests
    // ==========================================

    public function testCreateDeviceChartIteratesThroughAllTypes(): void
    {
        $type1 = $this->createMock(DeviceChartInterface::class);
        $type1->expects($this->once())->method('getType')->willReturn('type1');

        $type2 = $this->createMock(DeviceChartInterface::class);
        $type2->expects($this->once())->method('getType')->willReturn('type2');

        $type3 = $this->createMock(DeviceChartInterface::class);
        $type3->expects($this->once())->method('getType')->willReturn('target');
        $type3->method('formatData')->willReturn(['found' => true]);

        $this->repository->method('getDeviceChartData')->willReturn([]);

        $handler = $this->createHandler([$type1, $type2, $type3]);
        $result = $handler->createDeviceChart($this->device, 'target');

        $this->assertEquals(['found' => true], $result);
    }

    public function testCreateDeviceDataChartIteratesThroughAllTypes(): void
    {
        $type1 = $this->createMock(DeviceDataChartInterface::class);
        $type1->expects($this->once())->method('getType')->willReturn('type1');

        $type2 = $this->createMock(DeviceDataChartInterface::class);
        $type2->expects($this->once())->method('getType')->willReturn('type2');

        $type3 = $this->createMock(DeviceDataChartInterface::class);
        $type3->expects($this->once())->method('getType')->willReturn('target');
        $type3->method('formatData')->willReturn(['found' => true]);

        $this->repository->method('getChartData')->willReturn([]);

        $handler = $this->createHandler([], [$type1, $type2, $type3]);
        $result = $handler->createDeviceDataChart($this->device, 'target');

        $this->assertEquals(['found' => true], $result);
    }

    public function testCreateDeviceChartStopsAtFirstMatch(): void
    {
        $type1 = $this->createMock(DeviceChartInterface::class);
        $type1->method('getType')->willReturn('target');
        $type1->method('formatData')->willReturn(['first' => true]);

        $type2 = $this->createMock(DeviceChartInterface::class);
        $type2->expects($this->never())->method('getType');

        $this->repository->method('getDeviceChartData')->willReturn([]);

        $handler = $this->createHandler([$type1, $type2]);
        $result = $handler->createDeviceChart($this->device, 'target');

        $this->assertEquals(['first' => true], $result);
    }
}
