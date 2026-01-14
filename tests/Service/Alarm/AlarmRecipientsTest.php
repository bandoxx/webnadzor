<?php

namespace App\Tests\Service\Alarm;

use App\Entity\Device;
use App\Entity\DeviceAlarm;
use App\Entity\DeviceAlarmSetupEntry;
use App\Entity\DeviceAlarmSetupGeneral;
use App\Service\Alarm\AlarmRecipients;
use App\Service\Alarm\Types\DeviceSupplyOff;
use App\Service\Alarm\Types\HumidityHigh;
use App\Service\Alarm\Types\Standalone\DeviceOffline;
use App\Service\Alarm\Types\TemperatureHigh;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;

class AlarmRecipientsTest extends TestCase
{
    private AlarmRecipients $alarmRecipients;

    protected function setUp(): void
    {
        $this->alarmRecipients = new AlarmRecipients();
    }

    public function testGetRecipientsForSmsReturnsEmptyArrayWhenNoSettings(): void
    {
        $device = $this->createMockDevice([], []);
        $alarm = $this->createMockAlarm($device, DeviceSupplyOff::TYPE, null);

        $recipients = $this->alarmRecipients->getRecipientsForSms($alarm);

        $this->assertIsArray($recipients);
        $this->assertEmpty($recipients);
    }

    public function testGetRecipientsForSmsReturnsPhoneForDeviceLevelAlarmWithGeneralSettings(): void
    {
        $generalSetting = $this->createMockGeneralSetting(
            phoneNumber: '+385912345678',
            smsActive: true,
            devicePowerSupplyOffActive: true
        );
        $device = $this->createMockDevice([$generalSetting], []);
        $alarm = $this->createMockAlarm($device, DeviceSupplyOff::TYPE, null);

        $recipients = $this->alarmRecipients->getRecipientsForSms($alarm);

        $this->assertCount(1, $recipients);
        $this->assertEquals('385912345678', $recipients[0]);
    }

    public function testGetRecipientsForSmsReturnsPhoneForDeviceOfflineAlarm(): void
    {
        $generalSetting = $this->createMockGeneralSetting(
            phoneNumber: '+385912345678',
            smsActive: true,
            devicePowerSupplyOffActive: true
        );
        $device = $this->createMockDevice([$generalSetting], []);
        $alarm = $this->createMockAlarm($device, DeviceOffline::TYPE, null);

        $recipients = $this->alarmRecipients->getRecipientsForSms($alarm);

        $this->assertCount(1, $recipients);
        $this->assertEquals('385912345678', $recipients[0]);
    }

    public function testGetRecipientsForSmsSkipsInactiveSms(): void
    {
        $generalSetting = $this->createMockGeneralSetting(
            phoneNumber: '+385912345678',
            smsActive: false,
            devicePowerSupplyOffActive: true
        );
        $device = $this->createMockDevice([$generalSetting], []);
        $alarm = $this->createMockAlarm($device, DeviceSupplyOff::TYPE, null);

        $recipients = $this->alarmRecipients->getRecipientsForSms($alarm);

        $this->assertEmpty($recipients);
    }

    public function testGetRecipientsForSmsSkipsInactiveDevicePowerSupply(): void
    {
        $generalSetting = $this->createMockGeneralSetting(
            phoneNumber: '+385912345678',
            smsActive: true,
            devicePowerSupplyOffActive: false
        );
        $device = $this->createMockDevice([$generalSetting], []);
        $alarm = $this->createMockAlarm($device, DeviceSupplyOff::TYPE, null);

        $recipients = $this->alarmRecipients->getRecipientsForSms($alarm);

        $this->assertEmpty($recipients);
    }

    public function testGetRecipientsForSmsReturnsPhoneForTemperatureAlarm(): void
    {
        $entrySetting = $this->createMockEntrySetting(
            entry: 1,
            phoneNumber: '+385912345678',
            smsActive: true,
            temperatureActive: true,
            humidityActive: false
        );
        $device = $this->createMockDevice([], [$entrySetting]);
        $alarm = $this->createMockAlarm($device, TemperatureHigh::TYPE, 1);

        $recipients = $this->alarmRecipients->getRecipientsForSms($alarm);

        $this->assertCount(1, $recipients);
        $this->assertEquals('385912345678', $recipients[0]);
    }

    public function testGetRecipientsForSmsReturnsPhoneForHumidityAlarm(): void
    {
        $entrySetting = $this->createMockEntrySetting(
            entry: 2,
            phoneNumber: '+385998877665',
            smsActive: true,
            temperatureActive: false,
            humidityActive: true
        );
        $device = $this->createMockDevice([], [$entrySetting]);
        $alarm = $this->createMockAlarm($device, HumidityHigh::TYPE, 2);

        $recipients = $this->alarmRecipients->getRecipientsForSms($alarm);

        $this->assertCount(1, $recipients);
        $this->assertEquals('385998877665', $recipients[0]);
    }

    public function testGetRecipientsForSmsSkipsWrongEntry(): void
    {
        $entrySetting = $this->createMockEntrySetting(
            entry: 1,
            phoneNumber: '+385912345678',
            smsActive: true,
            temperatureActive: true,
            humidityActive: true
        );
        $device = $this->createMockDevice([], [$entrySetting]);
        $alarm = $this->createMockAlarm($device, TemperatureHigh::TYPE, 2);

        $recipients = $this->alarmRecipients->getRecipientsForSms($alarm);

        $this->assertEmpty($recipients);
    }

    public function testGetRecipientsForSmsSkipsInactiveTemperature(): void
    {
        $entrySetting = $this->createMockEntrySetting(
            entry: 1,
            phoneNumber: '+385912345678',
            smsActive: true,
            temperatureActive: false,
            humidityActive: true
        );
        $device = $this->createMockDevice([], [$entrySetting]);
        $alarm = $this->createMockAlarm($device, TemperatureHigh::TYPE, 1);

        $recipients = $this->alarmRecipients->getRecipientsForSms($alarm);

        $this->assertEmpty($recipients);
    }

    public function testGetRecipientsForVoiceMessageReturnsPhoneWhenVoiceActive(): void
    {
        $generalSetting = $this->createMockGeneralSetting(
            phoneNumber: '+385912345678',
            smsActive: false,
            devicePowerSupplyOffActive: true,
            voiceActive: true
        );
        $device = $this->createMockDevice([$generalSetting], []);
        $alarm = $this->createMockAlarm($device, DeviceSupplyOff::TYPE, null);

        $recipients = $this->alarmRecipients->getRecipientsForVoiceMessage($alarm);

        $this->assertCount(1, $recipients);
        $this->assertEquals('385912345678', $recipients[0]);
    }

    public function testGetRecipientsForSmsReturnsMultipleRecipients(): void
    {
        $generalSetting1 = $this->createMockGeneralSetting(
            phoneNumber: '+385912345678',
            smsActive: true,
            devicePowerSupplyOffActive: true
        );
        $generalSetting2 = $this->createMockGeneralSetting(
            phoneNumber: '+385998877665',
            smsActive: true,
            devicePowerSupplyOffActive: true
        );
        $device = $this->createMockDevice([$generalSetting1, $generalSetting2], []);
        $alarm = $this->createMockAlarm($device, DeviceSupplyOff::TYPE, null);

        $recipients = $this->alarmRecipients->getRecipientsForSms($alarm);

        $this->assertCount(2, $recipients);
        $this->assertContains('385912345678', $recipients);
        $this->assertContains('385998877665', $recipients);
    }

    private function createMockDevice(array $generalSettings, array $entrySettings): Device
    {
        $device = $this->createMock(Device::class);
        $device->method('getDeviceAlarmSetupGenerals')
            ->willReturn(new ArrayCollection($generalSettings));
        $device->method('getDeviceAlarmSetupEntries')
            ->willReturn(new ArrayCollection($entrySettings));

        return $device;
    }

    private function createMockAlarm(Device $device, string $type, ?int $sensor): DeviceAlarm
    {
        $alarm = $this->createMock(DeviceAlarm::class);
        $alarm->method('getDevice')->willReturn($device);
        $alarm->method('getType')->willReturn($type);
        $alarm->method('getSensor')->willReturn($sensor !== null ? (string) $sensor : null);

        return $alarm;
    }

    private function createMockGeneralSetting(
        string $phoneNumber,
        bool $smsActive,
        bool $devicePowerSupplyOffActive,
        bool $voiceActive = false
    ): DeviceAlarmSetupGeneral {
        $setting = $this->createMock(DeviceAlarmSetupGeneral::class);
        $setting->method('getPhoneNumberWithoutPlus')->willReturn(ltrim($phoneNumber, '+'));
        $setting->method('isSmsActive')->willReturn($smsActive);
        $setting->method('isVoiceMessageActive')->willReturn($voiceActive);
        $setting->method('isDevicePowerSupplyOffActive')->willReturn($devicePowerSupplyOffActive);

        return $setting;
    }

    private function createMockEntrySetting(
        int $entry,
        string $phoneNumber,
        bool $smsActive,
        bool $temperatureActive,
        bool $humidityActive,
        bool $voiceActive = false
    ): DeviceAlarmSetupEntry {
        $setting = $this->createMock(DeviceAlarmSetupEntry::class);
        $setting->method('getEntry')->willReturn($entry);
        $setting->method('getPhoneNumberWithoutPlus')->willReturn(ltrim($phoneNumber, '+'));
        $setting->method('isSmsActive')->willReturn($smsActive);
        $setting->method('isVoiceMessageActive')->willReturn($voiceActive);
        $setting->method('isTemperatureActive')->willReturn($temperatureActive);
        $setting->method('isHumidityActive')->willReturn($humidityActive);

        return $setting;
    }
}
