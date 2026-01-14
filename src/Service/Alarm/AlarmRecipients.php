<?php

namespace App\Service\Alarm;

use App\Entity\Device;
use App\Entity\DeviceAlarm;
use App\Entity\DeviceAlarmSetupEntry;
use App\Entity\DeviceAlarmSetupGeneral;

class AlarmRecipients
{
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

        $isGeneralAlarm = $alarm->getSensor() === null;

        if ($isGeneralAlarm) {
            return $this->collectGeneralAlarmRecipients($device, $alarm, $notificationType);
        }

        return $this->collectEntryAlarmRecipients($device, $alarm, $notificationType);
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
        if (AlarmTypeGroups::isDeviceLevelAlarm($alarm->getType()) && $alarmSetting->isDevicePowerSupplyOffActive()) {
            return $alarmSetting->getPhoneNumberWithoutPlus();
        }

        return null;
    }

    private function getRecipientForEntryAlarm(DeviceAlarm $alarm, DeviceAlarmSetupEntry $alarmSetting): ?string
    {
        if ((int) $alarm->getSensor() !== $alarmSetting->getEntry()) {
            return null;
        }

        $alarmType = $alarm->getType();

        if (AlarmTypeGroups::isHumidityAlarm($alarmType) && $alarmSetting->isHumidityActive()) {
            return $alarmSetting->getPhoneNumberWithoutPlus();
        }

        if (AlarmTypeGroups::isTemperatureAlarm($alarmType) && $alarmSetting->isTemperatureActive()) {
            return $alarmSetting->getPhoneNumberWithoutPlus();
        }

        return null;
    }
}
