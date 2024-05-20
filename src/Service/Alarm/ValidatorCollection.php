<?php

namespace App\Service\Alarm;

use App\Entity\ClientSetting;
use App\Entity\DeviceData;

class ValidatorCollection
{

    /**
     * @param AlarmHandlerInterface[] $validators
     */
    public function __construct(private iterable $validators) {}

    public function validate(DeviceData $deviceData, ClientSetting $clientSetting): void
    {
        foreach ($this->validators as $validator) {
            $validator->validate($deviceData, $clientSetting);
        }
    }
}