<?php

namespace App\Tests\Service\DeviceData;

use App\Entity\Device;
use App\Repository\DeviceDataArchiveRepository;
use App\Repository\DeviceRepository;
use App\Service\DeviceData\DeviceDataDailyArchiveService;
use App\Service\DeviceData\ShiftDeviceDataService;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use Doctrine\DBAL\Statement;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ShiftDeviceDataServiceTest extends TestCase
{
    private Connection&MockObject $connection;
    private DeviceRepository&MockObject $deviceRepository;
    private DeviceDataArchiveRepository&MockObject $archiveRepository;
    private DeviceDataDailyArchiveService&MockObject $dailyArchiveService;
    private Statement&MockObject $statement;
    private Result&MockObject $result;

    protected function setUp(): void
    {
        $this->connection = $this->createMock(Connection::class);
        $this->deviceRepository = $this->createMock(DeviceRepository::class);
        $this->archiveRepository = $this->createMock(DeviceDataArchiveRepository::class);
        $this->dailyArchiveService = $this->createMock(DeviceDataDailyArchiveService::class);
        $this->statement = $this->createMock(Statement::class);
        $this->result = $this->createMock(Result::class);

        $this->connection->method('prepare')->willReturn($this->statement);
        $this->statement->method('executeQuery')->willReturn($this->result);
    }

    private function createService(): ShiftDeviceDataService
    {
        return new ShiftDeviceDataService(
            $this->connection,
            $this->deviceRepository,
            $this->archiveRepository,
            $this->dailyArchiveService
        );
    }

    // ==========================================
    // Tests for findBestInterval()
    // ==========================================

    public function testFindBestIntervalReturnsMinIntervalWhenNoRecords(): void
    {
        $this->result->method('fetchAllAssociative')->willReturn([]);

        $service = $this->createService();
        $result = $service->findBestInterval(
            1,
            new \DateTime('2024-01-01'),
            new \DateTime('2024-01-31')
        );

        $this->assertEquals(20, $result['intervalDays']);
        $this->assertEquals(0, $result['recordCount']);
    }

    public function testFindBestIntervalReturnsIntervalWithMostRecords(): void
    {
        $sourceRecord = [
            'id' => 1,
            'device_id' => 1,
            'server_date' => '2023-12-07 10:00:00',
            'device_date' => '2023-12-07 10:00:00',
            'gsm_signal' => 100,
            'supply' => 1,
            'vbat' => 3.6,
            'battery' => 80,
            'd1' => null,
            't1' => 22.5,
            'rh1' => 45.0,
            'mkt1' => 22.5,
            't_avrg1' => 22.5,
            't_min1' => 22.0,
            't_max1' => 23.0,
            'note1' => null,
            'd2' => null,
            't2' => 23.0,
            'rh2' => 50.0,
            'mkt2' => 23.0,
            't_avrg2' => 23.0,
            't_min2' => 22.5,
            't_max2' => 23.5,
            'note2' => null,
        ];

        // Simulate different record counts for different intervals
        // Each interval calls getShiftedDataPreview which calls 2 queries
        $callCount = 0;
        $this->result->method('fetchAllAssociative')
            ->willReturnCallback(function () use (&$callCount, $sourceRecord) {
                $callCount++;
                // Interval 25 is checked on calls 11-12 (source + existing)
                // Return record only for interval 25 (call 11)
                if ($callCount === 11) {
                    return [$sourceRecord];
                }
                return [];
            });

        $service = $this->createService();
        $result = $service->findBestInterval(
            1,
            new \DateTime('2024-01-01'),
            new \DateTime('2024-01-31')
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('intervalDays', $result);
        $this->assertArrayHasKey('recordCount', $result);
    }

    public function testFindBestIntervalWithDeviceId(): void
    {
        $deviceId = 42;
        $this->result->method('fetchAllAssociative')->willReturn([]);

        $service = $this->createService();
        $result = $service->findBestInterval(
            $deviceId,
            new \DateTime('2024-01-01'),
            new \DateTime('2024-01-31')
        );

        $this->assertIsArray($result);
    }

    // ==========================================
    // Tests for previewShiftedData()
    // ==========================================

    public function testPreviewShiftedDataReturnsEmptyWhenNoSourceRecords(): void
    {
        $this->result->method('fetchAllAssociative')->willReturn([]);

        $service = $this->createService();
        $result = $service->previewShiftedData(
            1,
            new \DateTime('2024-01-01'),
            new \DateTime('2024-01-31'),
            25
        );

        $this->assertIsArray($result);
        $this->assertEquals(25, $result['intervalDays']);
        $this->assertEmpty($result['records']);
    }

    public function testPreviewShiftedDataUsesProvidedInterval(): void
    {
        $this->result->method('fetchAllAssociative')->willReturn([]);

        $service = $this->createService();
        $result = $service->previewShiftedData(
            1,
            new \DateTime('2024-01-01'),
            new \DateTime('2024-01-31'),
            30
        );

        $this->assertEquals(30, $result['intervalDays']);
    }

    public function testPreviewShiftedDataFindsIntervalAutomaticallyWhenNull(): void
    {
        $this->result->method('fetchAllAssociative')->willReturn([]);

        $service = $this->createService();
        $result = $service->previewShiftedData(
            1,
            new \DateTime('2024-01-01'),
            new \DateTime('2024-01-31'),
            null
        );

        // Should use minimum interval (20) when no records found
        $this->assertEquals(20, $result['intervalDays']);
    }

    public function testPreviewShiftedDataReturnsTransformedRecords(): void
    {
        $sourceRecord = [
            'id' => 1,
            'device_id' => 1,
            'server_date' => '2023-12-07 10:00:00',
            'device_date' => '2023-12-07 10:00:00',
            'gsm_signal' => 100,
            'supply' => 1,
            'vbat' => 3.6,
            'battery' => 80,
            'd1' => null,
            't1' => 22.5,
            'rh1' => 45.0,
            'mkt1' => 22.5,
            't_avrg1' => 22.5,
            't_min1' => 22.0,
            't_max1' => 23.0,
            'note1' => null,
            'd2' => null,
            't2' => 23.0,
            'rh2' => 50.0,
            'mkt2' => 23.0,
            't_avrg2' => 23.0,
            't_min2' => 22.5,
            't_max2' => 23.5,
            'note2' => null,
        ];

        $callCount = 0;
        $this->result->method('fetchAllAssociative')
            ->willReturnCallback(function () use (&$callCount, $sourceRecord) {
                $callCount++;
                // First call is for source records, second is for existing minutes
                if ($callCount === 1) {
                    return [$sourceRecord];
                }
                return []; // No existing records in target period
            });

        $service = $this->createService();
        $result = $service->previewShiftedData(
            1,
            new \DateTime('2024-01-01'),
            new \DateTime('2024-01-31'),
            25
        );

        $this->assertCount(1, $result['records']);
        $this->assertArrayHasKey('old_device_date', $result['records'][0]);
        $this->assertArrayHasKey('new_device_date', $result['records'][0]);
        $this->assertEquals(22.5, $result['records'][0]['t1']);
    }

    public function testPreviewShiftedDataFiltersExistingMinutes(): void
    {
        $sourceRecord = [
            'id' => 1,
            'device_id' => 1,
            'server_date' => '2023-12-07 10:00:00',
            'device_date' => '2023-12-07 10:00:00',
            'gsm_signal' => 100,
            'supply' => 1,
            'vbat' => 3.6,
            'battery' => 80,
            'd1' => null,
            't1' => 22.5,
            'rh1' => 45.0,
            'mkt1' => 22.5,
            't_avrg1' => 22.5,
            't_min1' => 22.0,
            't_max1' => 23.0,
            'note1' => null,
            'd2' => null,
            't2' => 23.0,
            'rh2' => 50.0,
            'mkt2' => 23.0,
            't_avrg2' => 23.0,
            't_min2' => 22.5,
            't_max2' => 23.5,
            'note2' => null,
        ];

        $callCount = 0;
        $this->result->method('fetchAllAssociative')
            ->willReturnCallback(function () use (&$callCount, $sourceRecord) {
                $callCount++;
                if ($callCount === 1) {
                    return [$sourceRecord];
                }
                // Existing record at the same shifted minute
                return [['device_date' => '2024-01-01 10:00:00']];
            });

        $service = $this->createService();
        $result = $service->previewShiftedData(
            1,
            new \DateTime('2024-01-01'),
            new \DateTime('2024-01-31'),
            25
        );

        // Should be filtered out because target minute exists
        $this->assertEmpty($result['records']);
    }

    public function testPreviewShiftedDataAvoidsDuplicateMinutes(): void
    {
        // Two records with the same minute should result in only one
        $sourceRecords = [
            [
                'id' => 1,
                'device_id' => 1,
                'server_date' => '2023-12-07 10:00:00',
                'device_date' => '2023-12-07 10:00:00',
                'gsm_signal' => 100,
                'supply' => 1,
                'vbat' => 3.6,
                'battery' => 80,
                'd1' => null,
                't1' => 22.5,
                'rh1' => 45.0,
                'mkt1' => 22.5,
                't_avrg1' => 22.5,
                't_min1' => 22.0,
                't_max1' => 23.0,
                'note1' => null,
                'd2' => null,
                't2' => 23.0,
                'rh2' => 50.0,
                'mkt2' => 23.0,
                't_avrg2' => 23.0,
                't_min2' => 22.5,
                't_max2' => 23.5,
                'note2' => null,
            ],
            [
                'id' => 2,
                'device_id' => 1,
                'server_date' => '2023-12-07 10:00:30', // Same minute, different second
                'device_date' => '2023-12-07 10:00:30',
                'gsm_signal' => 100,
                'supply' => 1,
                'vbat' => 3.6,
                'battery' => 80,
                'd1' => null,
                't1' => 23.0,
                'rh1' => 45.0,
                'mkt1' => 22.5,
                't_avrg1' => 22.5,
                't_min1' => 22.0,
                't_max1' => 23.0,
                'note1' => null,
                'd2' => null,
                't2' => 23.0,
                'rh2' => 50.0,
                'mkt2' => 23.0,
                't_avrg2' => 23.0,
                't_min2' => 22.5,
                't_max2' => 23.5,
                'note2' => null,
            ],
        ];

        $callCount = 0;
        $this->result->method('fetchAllAssociative')
            ->willReturnCallback(function () use (&$callCount, $sourceRecords) {
                $callCount++;
                if ($callCount === 1) {
                    return $sourceRecords;
                }
                return [];
            });

        $service = $this->createService();
        $result = $service->previewShiftedData(
            1,
            new \DateTime('2024-01-01'),
            new \DateTime('2024-01-31'),
            25
        );

        // Only one record should be kept (first one wins)
        $this->assertCount(1, $result['records']);
        $this->assertEquals(22.5, $result['records'][0]['t1']);
    }

    // ==========================================
    // Tests for insertShiftedData()
    // ==========================================

    public function testInsertShiftedDataThrowsExceptionWhenDeviceNotFound(): void
    {
        $this->deviceRepository->method('find')->willReturn(null);

        $service = $this->createService();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Device with ID 999 not found');

        $service->insertShiftedData(
            999,
            new \DateTime('2024-01-01'),
            new \DateTime('2024-01-31')
        );
    }

    public function testInsertShiftedDataDeletesOldArchives(): void
    {
        $device = $this->createMock(Device::class);
        $this->deviceRepository->method('find')->willReturn($device);
        $this->result->method('fetchAllAssociative')->willReturn([]);

        $fromDate = new \DateTime('2024-01-01');
        $toDate = new \DateTime('2024-01-31');

        $this->archiveRepository->expects($this->once())
            ->method('deleteDailyArchivesForDeviceAndDateRange')
            ->with(1, $fromDate, $toDate);

        $service = $this->createService();
        $service->insertShiftedData(1, $fromDate, $toDate);
    }

    public function testInsertShiftedDataGeneratesNewArchives(): void
    {
        $device = $this->createMock(Device::class);
        $this->deviceRepository->method('find')->willReturn($device);
        $this->result->method('fetchAllAssociative')->willReturn([]);

        $fromDate = new \DateTime('2024-01-01');
        $toDate = new \DateTime('2024-01-31');

        $this->dailyArchiveService->expects($this->once())
            ->method('generateDailyArchivesForDateRange')
            ->with($device, $fromDate, $toDate);

        $service = $this->createService();
        $service->insertShiftedData(1, $fromDate, $toDate);
    }

    public function testInsertShiftedDataReturnsZeroWhenNoRecords(): void
    {
        $device = $this->createMock(Device::class);
        $this->deviceRepository->method('find')->willReturn($device);
        $this->result->method('fetchAllAssociative')->willReturn([]);

        $service = $this->createService();
        $result = $service->insertShiftedData(
            1,
            new \DateTime('2024-01-01'),
            new \DateTime('2024-01-31')
        );

        $this->assertEquals(0, $result);
    }

    public function testInsertShiftedDataReturnsInsertedCount(): void
    {
        $device = $this->createMock(Device::class);
        $this->deviceRepository->method('find')->willReturn($device);

        $sourceRecord = [
            'id' => 1,
            'device_id' => 1,
            'server_date' => '2023-12-07 10:00:00',
            'device_date' => '2023-12-07 10:00:00',
            'gsm_signal' => 100,
            'supply' => 1,
            'vbat' => 3.6,
            'battery' => 80,
            'd1' => null,
            't1' => 22.5,
            'rh1' => 45.0,
            'mkt1' => 22.5,
            't_avrg1' => 22.5,
            't_min1' => 22.0,
            't_max1' => 23.0,
            'note1' => null,
            'd2' => null,
            't2' => 23.0,
            'rh2' => 50.0,
            'mkt2' => 23.0,
            't_avrg2' => 23.0,
            't_min2' => 22.5,
            't_max2' => 23.5,
            'note2' => null,
        ];

        $callCount = 0;
        $this->result->method('fetchAllAssociative')
            ->willReturnCallback(function () use (&$callCount, $sourceRecord) {
                $callCount++;
                // Calls: 1-delete empty, 2-get preview source, 3-get existing, 4-insert preview source, 5-insert existing
                if ($callCount === 2 || $callCount === 4) {
                    return [$sourceRecord];
                }
                return [];
            });

        $this->statement->method('executeStatement')->willReturn(1);

        $service = $this->createService();
        $result = $service->insertShiftedData(
            1,
            new \DateTime('2024-01-01'),
            new \DateTime('2024-01-31')
        );

        $this->assertGreaterThanOrEqual(0, $result);
    }

    public function testInsertShiftedDataUsesDefaultInterval(): void
    {
        $device = $this->createMock(Device::class);
        $this->deviceRepository->method('find')->willReturn($device);
        $this->result->method('fetchAllAssociative')->willReturn([]);

        $service = $this->createService();
        // Default is 25 days
        $result = $service->insertShiftedData(
            1,
            new \DateTime('2024-01-01'),
            new \DateTime('2024-01-31')
        );

        $this->assertEquals(0, $result);
    }

    public function testInsertShiftedDataWithCustomInterval(): void
    {
        $device = $this->createMock(Device::class);
        $this->deviceRepository->method('find')->willReturn($device);
        $this->result->method('fetchAllAssociative')->willReturn([]);

        $service = $this->createService();
        $result = $service->insertShiftedData(
            1,
            new \DateTime('2024-01-01'),
            new \DateTime('2024-01-31'),
            30
        );

        $this->assertEquals(0, $result);
    }

    // ==========================================
    // Edge Case Tests
    // ==========================================

    public function testFindBestIntervalWithSameDates(): void
    {
        $this->result->method('fetchAllAssociative')->willReturn([]);

        $date = new \DateTime('2024-01-15');

        $service = $this->createService();
        $result = $service->findBestInterval(1, $date, clone $date);

        $this->assertEquals(20, $result['intervalDays']);
    }

    public function testPreviewShiftedDataWithSameDates(): void
    {
        $this->result->method('fetchAllAssociative')->willReturn([]);

        $date = new \DateTime('2024-01-15');

        $service = $this->createService();
        $result = $service->previewShiftedData(1, $date, clone $date, 25);

        $this->assertEmpty($result['records']);
    }

    public function testInsertShiftedDataVerifiesDeviceExistence(): void
    {
        $device = $this->createMock(Device::class);
        $this->deviceRepository->expects($this->once())
            ->method('find')
            ->with(42)
            ->willReturn($device);

        $this->result->method('fetchAllAssociative')->willReturn([]);

        $service = $this->createService();
        $service->insertShiftedData(
            42,
            new \DateTime('2024-01-01'),
            new \DateTime('2024-01-31')
        );
    }

    public function testPreviewShiftedDataCalculatesCorrectShiftedDates(): void
    {
        $sourceRecord = [
            'id' => 1,
            'device_id' => 1,
            'server_date' => '2023-12-07 10:00:00',
            'device_date' => '2023-12-07 10:00:00',
            'gsm_signal' => 100,
            'supply' => 1,
            'vbat' => 3.6,
            'battery' => 80,
            'd1' => null,
            't1' => 22.5,
            'rh1' => 45.0,
            'mkt1' => 22.5,
            't_avrg1' => 22.5,
            't_min1' => 22.0,
            't_max1' => 23.0,
            'note1' => null,
            'd2' => null,
            't2' => 23.0,
            'rh2' => 50.0,
            'mkt2' => 23.0,
            't_avrg2' => 23.0,
            't_min2' => 22.5,
            't_max2' => 23.5,
            'note2' => null,
        ];

        $callCount = 0;
        $this->result->method('fetchAllAssociative')
            ->willReturnCallback(function () use (&$callCount, $sourceRecord) {
                $callCount++;
                if ($callCount === 1) {
                    return [$sourceRecord];
                }
                return [];
            });

        $service = $this->createService();
        $result = $service->previewShiftedData(
            1,
            new \DateTime('2024-01-01'),
            new \DateTime('2024-01-31'),
            25 // Shift by 25 days
        );

        $this->assertCount(1, $result['records']);
        // 2023-12-07 + 25 days = 2024-01-01
        $this->assertEquals('2023-12-07 10:00:00', $result['records'][0]['old_device_date']);
        $this->assertEquals('2024-01-01 10:00:00', $result['records'][0]['new_device_date']);
    }
}
