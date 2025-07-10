<?php

namespace App\Service\Device\Updater;

use App\Entity\Device;
use App\Service\Device\Validator\DeviceDataValidator;

class DeviceEmailConfigUpdater
{
    public function __construct(
        private readonly DeviceDataValidator $validator
    ) {
    }

    /**
     * Update SMTP email configuration
     *
     * @param Device $device The device to update
     * @param array|null $smtp The SMTP email data
     */
    public function updateSmtpEmails(Device $device, ?array $smtp = []): void
    {
        $smtpEmails = array_values(array_unique(array_filter($smtp)));

        foreach ($smtpEmails as $email) {
            $this->validator->validateEmail($email);
        }

        $device->setAlarmEmail($smtpEmails);
    }

    /**
     * Update application email configuration
     *
     * @param Device $device The device to update
     * @param array|null $applicationEmail The application email data
     */
    public function updateApplicationEmails(Device $device, ?array $applicationEmail = []): void
    {
        $applicationEmails = array_values(array_unique(array_filter($applicationEmail)));

        foreach ($applicationEmails as $email) {
            $this->validator->validateEmail($email);
        }

        $device->setApplicationEmailList($applicationEmails);
    }
}