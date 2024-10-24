<?php

namespace App\Service\Device\Updater;

use App\Entity\Device;
use App\Entity\DeviceAlarmSetupEntry;

class DeviceAlarmSetupEntryFactory
{
    public function create(
        Device $device,
        int $entry,
        string $phoneNumber,
        bool $digitalEntryActive,
        bool $digitalEntryActiveValue,
        bool $humidityActive,
        bool $temperatureActive,
        bool $smsActive,
        bool $voiceMessageActive,

    ): DeviceAlarmSetupEntry
    {
        return (new DeviceAlarmSetupEntry())
            ->setDevice($device)
            ->setPhoneNumber($phoneNumber)
            ->setSmsActive($smsActive)
            ->setVoiceMessageActive($voiceMessageActive)
            ->setEntry($entry)
            ->setDigitalEntryActive($digitalEntryActive)
            ->setDigitalEntryAlarmValue($digitalEntryActiveValue)
            ->setHumidityActive($humidityActive)
            ->setTemperatureActive($temperatureActive)
        ;
    }

}