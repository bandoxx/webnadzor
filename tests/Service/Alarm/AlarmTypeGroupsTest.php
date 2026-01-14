<?php

namespace App\Tests\Service\Alarm;

use App\Service\Alarm\AlarmTypeGroups;
use App\Service\Alarm\Types\DeviceSupplyOff;
use App\Service\Alarm\Types\HumidityHigh;
use App\Service\Alarm\Types\HumidityLow;
use App\Service\Alarm\Types\Standalone\DeviceOffline;
use App\Service\Alarm\Types\TemperatureHigh;
use App\Service\Alarm\Types\TemperatureLow;
use PHPUnit\Framework\TestCase;

class AlarmTypeGroupsTest extends TestCase
{
    /**
     * @dataProvider deviceLevelAlarmProvider
     */
    public function testIsDeviceLevelAlarmReturnsTrue(string $alarmType): void
    {
        $this->assertTrue(AlarmTypeGroups::isDeviceLevelAlarm($alarmType));
    }

    public static function deviceLevelAlarmProvider(): array
    {
        return [
            'device supply off' => [DeviceSupplyOff::TYPE],
            'device offline' => [DeviceOffline::TYPE],
        ];
    }

    /**
     * @dataProvider nonDeviceLevelAlarmProvider
     */
    public function testIsDeviceLevelAlarmReturnsFalse(string $alarmType): void
    {
        $this->assertFalse(AlarmTypeGroups::isDeviceLevelAlarm($alarmType));
    }

    public static function nonDeviceLevelAlarmProvider(): array
    {
        return [
            'temperature high' => [TemperatureHigh::TYPE],
            'temperature low' => [TemperatureLow::TYPE],
            'humidity high' => [HumidityHigh::TYPE],
            'humidity low' => [HumidityLow::TYPE],
            'unknown type' => ['unknown_alarm_type'],
            'empty string' => [''],
        ];
    }

    /**
     * @dataProvider temperatureAlarmProvider
     */
    public function testIsTemperatureAlarmReturnsTrue(string $alarmType): void
    {
        $this->assertTrue(AlarmTypeGroups::isTemperatureAlarm($alarmType));
    }

    public static function temperatureAlarmProvider(): array
    {
        return [
            'temperature high' => [TemperatureHigh::TYPE],
            'temperature low' => [TemperatureLow::TYPE],
        ];
    }

    /**
     * @dataProvider nonTemperatureAlarmProvider
     */
    public function testIsTemperatureAlarmReturnsFalse(string $alarmType): void
    {
        $this->assertFalse(AlarmTypeGroups::isTemperatureAlarm($alarmType));
    }

    public static function nonTemperatureAlarmProvider(): array
    {
        return [
            'device supply off' => [DeviceSupplyOff::TYPE],
            'device offline' => [DeviceOffline::TYPE],
            'humidity high' => [HumidityHigh::TYPE],
            'humidity low' => [HumidityLow::TYPE],
            'unknown type' => ['unknown_alarm_type'],
        ];
    }

    /**
     * @dataProvider humidityAlarmProvider
     */
    public function testIsHumidityAlarmReturnsTrue(string $alarmType): void
    {
        $this->assertTrue(AlarmTypeGroups::isHumidityAlarm($alarmType));
    }

    public static function humidityAlarmProvider(): array
    {
        return [
            'humidity high' => [HumidityHigh::TYPE],
            'humidity low' => [HumidityLow::TYPE],
        ];
    }

    /**
     * @dataProvider nonHumidityAlarmProvider
     */
    public function testIsHumidityAlarmReturnsFalse(string $alarmType): void
    {
        $this->assertFalse(AlarmTypeGroups::isHumidityAlarm($alarmType));
    }

    public static function nonHumidityAlarmProvider(): array
    {
        return [
            'device supply off' => [DeviceSupplyOff::TYPE],
            'device offline' => [DeviceOffline::TYPE],
            'temperature high' => [TemperatureHigh::TYPE],
            'temperature low' => [TemperatureLow::TYPE],
            'unknown type' => ['unknown_alarm_type'],
        ];
    }

    public function testConstantsContainCorrectTypes(): void
    {
        $this->assertContains(DeviceSupplyOff::TYPE, AlarmTypeGroups::DEVICE_LEVEL);
        $this->assertContains(DeviceOffline::TYPE, AlarmTypeGroups::DEVICE_LEVEL);
        $this->assertCount(2, AlarmTypeGroups::DEVICE_LEVEL);

        $this->assertContains(TemperatureHigh::TYPE, AlarmTypeGroups::TEMPERATURE);
        $this->assertContains(TemperatureLow::TYPE, AlarmTypeGroups::TEMPERATURE);
        $this->assertCount(2, AlarmTypeGroups::TEMPERATURE);

        $this->assertContains(HumidityHigh::TYPE, AlarmTypeGroups::HUMIDITY);
        $this->assertContains(HumidityLow::TYPE, AlarmTypeGroups::HUMIDITY);
        $this->assertCount(2, AlarmTypeGroups::HUMIDITY);
    }

    public function testAlarmTypesAreMutuallyExclusive(): void
    {
        $deviceLevel = AlarmTypeGroups::DEVICE_LEVEL;
        $temperature = AlarmTypeGroups::TEMPERATURE;
        $humidity = AlarmTypeGroups::HUMIDITY;

        $this->assertEmpty(array_intersect($deviceLevel, $temperature));
        $this->assertEmpty(array_intersect($deviceLevel, $humidity));
        $this->assertEmpty(array_intersect($temperature, $humidity));
    }
}
