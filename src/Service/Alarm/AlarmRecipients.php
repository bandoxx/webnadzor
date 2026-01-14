<?php

namespace App\Service\Alarm;

use App\Entity\Device;
use App\Entity\DeviceAlarm;
use App\Entity\DeviceAlarmSetupEntry;
use App\Entity\DeviceAlarmSetupGeneral;
use App\Service\Alarm\Types\DeviceSupplyOff;
use App\Service\Alarm\Types\Standalone\DeviceOffline;
use App\Service\Alarm\Types\HumidityHigh;
use App\Service\Alarm\Types\HumidityLow;
use App\Service\Alarm\Types\TemperatureHigh;
use App\Service\Alarm\Types\TemperatureLow;

class AlarmRecipients
{
    private const array DEVICE_LEVEL_ALARM_TYPES = [
        DeviceSupplyOff::TYPE,
        DeviceOffline::TYPE,
    ];

    private const array TEMPERATURE_ALARM_TYPES = [
        TemperatureLow::TYPE,
        TemperatureHigh::TYPE,
    ];

    private const array HUMIDITY_ALARM_TYPES = [
        HumidityLow::TYPE,
        HumidityHigh::TYPE,
    ];

    public function getRecipientsForSms(DeviceAlarm $alarm): array
    {
        return $this->getRecipientsByNotificationType($alarm, 'sms');
    }

    public function getRecipientsForVoiceMessage(DeviceAlarm $alarm): array
    {
        return $this->getRecipientsByNotificationType($alarm, 'voice');
    }

    private function getRecipientsByNotificationType(DeviceAlarm $alarm, string $notificationType): array
    {
        /** @var Device $device */
        $device = $alarm->getDevice();
        $recipients = [];

        $isGeneralAlarm = $alarm->getSensor() === null;

        if ($isGeneralAlarm) {
            $recipients = $this->collectGeneralAlarmRecipients($device, $alarm, $notificationType);
        } else {
            $recipients = $this->collectEntryAlarmRecipients($device, $alarm, $notificationType);
        }

        return $recipients;
    }

    private function collectGeneralAlarmRecipients(Device $device, DeviceAlarm $alarm, string $notificationType): array
    {
        $recipients = [];
        $alarmSettings = $device->getDeviceAlarmSetupGenerals()->toArray();

        foreach ($alarmSettings as $alarmSetting) {
            if (!$this->isNotificationTypeActive($alarmSetting, $notificationType)) {
                continue;
            }

            $recipient = $this->getRecipientForGeneralAlarm($alarm, $alarmSetting);
            if ($recipient !== null) {
                $recipients[] = $recipient;
            }
        }

        return $recipients;
    }

    private function collectEntryAlarmRecipients(Device $device, DeviceAlarm $alarm, string $notificationType): array
    {
        $recipients = [];
        $alarmSettings = $device->getDeviceAlarmSetupEntries()->toArray();

        foreach ($alarmSettings as $alarmSetting) {
            if (!$this->isNotificationTypeActive($alarmSetting, $notificationType)) {
                continue;
            }

            $recipient = $this->getRecipientForEntryAlarm($alarm, $alarmSetting);
            if ($recipient !== null) {
                $recipients[] = $recipient;
            }
        }

        return $recipients;
    }

    private function isNotificationTypeActive(DeviceAlarmSetupGeneral|DeviceAlarmSetupEntry $setting, string $type): bool
    {
        return match ($type) {
            'sms' => $setting->isSmsActive(),
            'voice' => $setting->isVoiceMessageActive(),
            default => false,
        };
    }

    private function getRecipientForGeneralAlarm(DeviceAlarm $alarm, DeviceAlarmSetupGeneral $alarmSetting): ?string
    {
        $isDeviceLevelAlarm = in_array($alarm->getType(), self::DEVICE_LEVEL_ALARM_TYPES, true);

        if ($isDeviceLevelAlarm && $alarmSetting->isDevicePowerSupplyOffActive()) {
            return $alarmSetting->getPhoneNumberWithoutPlus();
        }

        return null;
    }

    private function getRecipientForEntryAlarm(DeviceAlarm $alarm, DeviceAlarmSetupEntry $alarmSetting): ?string
    {
        $alarmSensor = $alarm->getSensor();
        $settingEntry = $alarmSetting->getEntry();

        if ($alarmSensor !== $settingEntry) {
            return null;
        }

        $alarmType = $alarm->getType();

        if ($this->isHumidityAlarm($alarmType) && $alarmSetting->isHumidityActive()) {
            return $alarmSetting->getPhoneNumberWithoutPlus();
        }

        if ($this->isTemperatureAlarm($alarmType) && $alarmSetting->isTemperatureActive()) {
            return $alarmSetting->getPhoneNumberWithoutPlus();
        }

        return null;
    }

    private function isTemperatureAlarm(string $alarmType): bool
    {
        return in_array($alarmType, self::TEMPERATURE_ALARM_TYPES, true);
    }

    private function isHumidityAlarm(string $alarmType): bool
    {
        return in_array($alarmType, self::HUMIDITY_ALARM_TYPES, true);
    }
}
