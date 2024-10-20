<?php

namespace App\Service\Alarm;

use App\Entity\Device;
use App\Entity\DeviceAlarm;
use App\Entity\DeviceAlarmSetupEntry;
use App\Entity\DeviceAlarmSetupGeneral;
use App\Service\Alarm\Types\DeviceSupplyOff;
use App\Service\Alarm\Types\HumidityHigh;
use App\Service\Alarm\Types\HumidityLow;
use App\Service\Alarm\Types\TemperatureHigh;
use App\Service\Alarm\Types\TemperatureLow;

class AlarmRecipients
{
    public function getRecipientsForSms(DeviceAlarm $alarm): array
    {
        /** @var Device $device */
        $device = $alarm->getDevice();
        $recipients = [];

        if ($alarm->getSensor() === null) {
            $alarmSettings = $device->getDeviceAlarmSetupGenerals()->toArray();

            foreach ($alarmSettings as $alarmSetting) {
                if ($alarmSetting->isSmsActive() === false) {
                    continue;
                }

                if ($recipient = $this->getRecipientsForGeneralAlarms($alarm, $alarmSetting)) {
                    $recipients[] = $recipient;
                }
            }
        } else {
            $alarmSettings = $device->getDeviceAlarmSetupEntries()->toArray();

            foreach ($alarmSettings as $alarmSetting) {
                if ($alarmSetting->isSmsActive() === false) {
                    continue;
                }

                if ($recipient = $this->getRecipientsForEntryAlarms($alarm, $alarmSetting)) {
                    $recipients[] = $recipient;
                }
            }
        }

        return $recipients;
    }

    public function getRecipientsForVoiceMessage(DeviceAlarm $alarm): array
    {
        /** @var Device $device */
        $device = $alarm->getDevice();
        $recipients = [];

        if ($alarm->getSensor() === null) {
            $alarmSettings = $device->getDeviceAlarmSetupGenerals()->toArray();

            foreach ($alarmSettings as $alarmSetting) {
                if ($alarmSetting->isVoiceMessageActive() === false) {
                    continue;
                }

                if ($recipient = $this->getRecipientsForGeneralAlarms($alarm, $alarmSetting)) {
                    $recipients[] = $recipient;
                }
            }
        } else {
            $alarmSettings = $device->getDeviceAlarmSetupEntries()->toArray();

            foreach ($alarmSettings as $alarmSetting) {
                if ($alarmSetting->isVoiceMessageActive() === false) {
                    continue;
                }

                if ($recipient = $this->getRecipientsForEntryAlarms($alarm, $alarmSetting)) {
                    $recipients[] = $recipient;
                }
            }
        }

        return $recipients;
    }

    private function getRecipientsForGeneralAlarms(DeviceAlarm $alarm, DeviceAlarmSetupGeneral $alarmSetting): ?string
    {
        if ($alarm->getType() === DeviceSupplyOff::TYPE && $alarmSetting->isDevicePowerSupplyOffActive()) {
            return $alarmSetting->getPhoneNumber();
        }

        return null;
    }

    private function getRecipientsForEntryAlarms(DeviceAlarm $alarm, DeviceAlarmSetupEntry $alarmSetting): ?string
    {
        if ($alarmSetting->isHumidityActive() && $alarmSetting->getEntry() == $alarm->getSensor() && in_array($alarm->getType(), [HumidityLow::TYPE, HumidityHigh::TYPE], true)) {
            return $alarmSetting->getPhoneNumber();
        }

        if ($alarmSetting->isTemperatureActive() && $alarmSetting->getEntry() == $alarm->getSensor() && in_array($alarm->getType(), [TemperatureLow::TYPE, TemperatureHigh::TYPE], true)) {
            return $alarmSetting->getPhoneNumber();
        }

        return null;
    }
}