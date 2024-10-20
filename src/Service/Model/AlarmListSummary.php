<?php

namespace App\Service\Model;

use App\Entity\DeviceAlarmLog;

class AlarmListSummary
{

    public function __construct(
        public string $name,
        public int $phoneSms = 0,
        public int $phoneVoice = 0
    ) {}

    public function add(string $type): void
    {
        if ($type === DeviceAlarmLog::TYPE_PHONE_SMS) {
            $this->phoneSms++;
            return;
        }

        if ($type === DeviceAlarmLog::TYPE_PHONE_VOICE) {
            $this->phoneVoice++;
            return;
        }
    }
}