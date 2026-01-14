<?php

namespace App\Tests\Service\Device\Updater;

use App\Entity\Device;
use App\Entity\DeviceAlarmSetupEntry;
use App\Entity\DeviceAlarmSetupGeneral;
use App\Service\Device\Updater\DeviceAlarmSetupEntryFactory;
use App\Service\Device\Updater\DeviceAlarmSetupGeneralFactory;
use App\Service\Device\Updater\DeviceAlarmUpdater;
use App\Service\Device\Validator\DeviceDataValidator;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DeviceAlarmUpdaterTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;
    private DeviceAlarmSetupEntryFactory&MockObject $entryFactory;
    private DeviceAlarmSetupGeneralFactory&MockObject $generalFactory;
    private DeviceDataValidator&MockObject $validator;
    private DeviceAlarmUpdater $updater;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->entryFactory = $this->createMock(DeviceAlarmSetupEntryFactory::class);
        $this->generalFactory = $this->createMock(DeviceAlarmSetupGeneralFactory::class);
        $this->validator = $this->createMock(DeviceDataValidator::class);

        $this->updater = new DeviceAlarmUpdater(
            $this->entityManager,
            $this->entryFactory,
            $this->generalFactory,
            $this->validator
        );
    }

    // ==================== updateAlarmSetupGeneral Tests ====================

    public function testUpdateAlarmSetupGeneralRemovesExistingSetups(): void
    {
        $existingSetup1 = $this->createMock(DeviceAlarmSetupGeneral::class);
        $existingSetup2 = $this->createMock(DeviceAlarmSetupGeneral::class);
        $device = $this->createMockDevice([$existingSetup1, $existingSetup2], []);

        $this->entityManager
            ->expects($this->exactly(2))
            ->method('remove')
            ->withConsecutive([$existingSetup1], [$existingSetup2]);

        $this->updater->updateAlarmSetupGeneral($device, []);
    }

    public function testUpdateAlarmSetupGeneralCreatesNewSetups(): void
    {
        $device = $this->createMockDevice([], []);
        $newSetup = $this->createMock(DeviceAlarmSetupGeneral::class);

        $data = [
            [
                'phone_number' => '+385912345678',
                'is_device_power_supply_active' => true,
                'is_sms_active' => true,
                'is_voice_message_active' => false,
            ],
        ];

        $this->validator
            ->expects($this->once())
            ->method('validatePhoneNumber')
            ->with('+385912345678');

        $this->generalFactory
            ->expects($this->once())
            ->method('create')
            ->with($device, '+385912345678', true, true, false)
            ->willReturn($newSetup);

        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($newSetup);

        $this->updater->updateAlarmSetupGeneral($device, $data);
    }

    public function testUpdateAlarmSetupGeneralCreatesMultipleSetups(): void
    {
        $device = $this->createMockDevice([], []);
        $setup1 = $this->createMock(DeviceAlarmSetupGeneral::class);
        $setup2 = $this->createMock(DeviceAlarmSetupGeneral::class);

        $data = [
            [
                'phone_number' => '+385912345678',
                'is_device_power_supply_active' => true,
                'is_sms_active' => true,
                'is_voice_message_active' => false,
            ],
            [
                'phone_number' => '+385998877665',
                'is_device_power_supply_active' => true,
                'is_sms_active' => false,
                'is_voice_message_active' => true,
            ],
        ];

        $this->validator
            ->expects($this->exactly(2))
            ->method('validatePhoneNumber');

        $this->generalFactory
            ->expects($this->exactly(2))
            ->method('create')
            ->willReturnOnConsecutiveCalls($setup1, $setup2);

        $this->entityManager
            ->expects($this->exactly(2))
            ->method('persist');

        $this->updater->updateAlarmSetupGeneral($device, $data);
    }

    // ==================== updateAlarmSetupEntry Tests ====================

    public function testUpdateAlarmSetupEntryRemovesExistingSetups(): void
    {
        $existingEntry1 = $this->createMock(DeviceAlarmSetupEntry::class);
        $existingEntry2 = $this->createMock(DeviceAlarmSetupEntry::class);
        $device = $this->createMockDevice([], [$existingEntry1, $existingEntry2]);

        $this->entityManager
            ->expects($this->exactly(2))
            ->method('remove');

        $this->updater->updateAlarmSetupEntry($device, 1, []);
    }

    public function testUpdateAlarmSetupEntryCreatesNewSetups(): void
    {
        $device = $this->createMockDevice([], []);
        $newEntry = $this->createMock(DeviceAlarmSetupEntry::class);

        $data = [
            [
                'phone_number' => '+385912345678',
                'is_digital_entry_active' => false,
                'digital_entry_alarm_value' => false,
                'is_humidity_active' => true,
                'is_temperature_active' => true,
                'is_sms_active' => true,
                'is_voice_message_active' => false,
            ],
        ];

        $this->validator
            ->expects($this->once())
            ->method('validatePhoneNumber')
            ->with('+385912345678');

        $this->entryFactory
            ->expects($this->once())
            ->method('create')
            ->with($device, 1, '+385912345678', false, false, true, true, true, false)
            ->willReturn($newEntry);

        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($newEntry);

        $this->updater->updateAlarmSetupEntry($device, 1, $data);
    }

    public function testUpdateAlarmSetupEntrySkipsEmptyPhoneNumbers(): void
    {
        $device = $this->createMockDevice([], []);

        $data = [
            [
                'phone_number' => '',
                'is_digital_entry_active' => false,
                'digital_entry_alarm_value' => false,
                'is_humidity_active' => true,
                'is_temperature_active' => true,
                'is_sms_active' => true,
                'is_voice_message_active' => false,
            ],
        ];

        $this->validator
            ->expects($this->never())
            ->method('validatePhoneNumber');

        $this->entryFactory
            ->expects($this->never())
            ->method('create');

        $this->entityManager
            ->expects($this->never())
            ->method('persist');

        $this->updater->updateAlarmSetupEntry($device, 1, $data);
    }

    public function testUpdateAlarmSetupEntryHandlesMixedData(): void
    {
        $device = $this->createMockDevice([], []);
        $newEntry = $this->createMock(DeviceAlarmSetupEntry::class);

        $data = [
            [
                'phone_number' => '',
                'is_digital_entry_active' => false,
                'digital_entry_alarm_value' => false,
                'is_humidity_active' => true,
                'is_temperature_active' => true,
                'is_sms_active' => true,
                'is_voice_message_active' => false,
            ],
            [
                'phone_number' => '+385912345678',
                'is_digital_entry_active' => true,
                'digital_entry_alarm_value' => true,
                'is_humidity_active' => false,
                'is_temperature_active' => true,
                'is_sms_active' => true,
                'is_voice_message_active' => true,
            ],
        ];

        $this->validator
            ->expects($this->once())
            ->method('validatePhoneNumber')
            ->with('+385912345678');

        $this->entryFactory
            ->expects($this->once())
            ->method('create')
            ->willReturn($newEntry);

        $this->entityManager
            ->expects($this->once())
            ->method('persist');

        $this->updater->updateAlarmSetupEntry($device, 2, $data);
    }

    public function testUpdateAlarmSetupEntryUsesCorrectEntryNumber(): void
    {
        $device = $this->createMockDevice([], []);
        $newEntry = $this->createMock(DeviceAlarmSetupEntry::class);

        $data = [
            [
                'phone_number' => '+385912345678',
                'is_digital_entry_active' => false,
                'digital_entry_alarm_value' => false,
                'is_humidity_active' => true,
                'is_temperature_active' => true,
                'is_sms_active' => true,
                'is_voice_message_active' => false,
            ],
        ];

        $this->entryFactory
            ->expects($this->once())
            ->method('create')
            ->with(
                $device,
                2,
                $this->anything(),
                $this->anything(),
                $this->anything(),
                $this->anything(),
                $this->anything(),
                $this->anything(),
                $this->anything()
            )
            ->willReturn($newEntry);

        $this->updater->updateAlarmSetupEntry($device, 2, $data);
    }

    // ==================== Helper Methods ====================

    private function createMockDevice(array $generalSetups, array $entrySetups): Device
    {
        $device = $this->createMock(Device::class);
        $device->method('getDeviceAlarmSetupGenerals')
            ->willReturn(new ArrayCollection($generalSetups));
        $device->method('getDeviceAlarmSetupEntries')
            ->willReturn(new ArrayCollection($entrySetups));

        return $device;
    }
}
