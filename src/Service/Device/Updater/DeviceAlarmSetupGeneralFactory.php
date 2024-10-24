<?php

namespace App\Service\Device\Updater;

use App\Entity\Device;
use App\Entity\DeviceAlarmSetupGeneral;

class DeviceAlarmSetupGeneralFactory
{
    public function create(Device $device, string $phoneNumber, bool $devicePowerSupplyOffActive, bool $smsActive, bool $voiceMessageActive): DeviceAlarmSetupGeneral
    {
        return (new DeviceAlarmSetupGeneral())
            ->setDevice($device)
            ->setPhoneNumber($phoneNumber)
            ->setSmsActive($smsActive)
            ->setVoiceMessageActive($voiceMessageActive)
            ->setDevicePowerSupplyOffActive($devicePowerSupplyOffActive)
        ;
    }
}