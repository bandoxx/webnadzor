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
     * Accepts both:
     * - a flat list of emails ["a@b.com", "c@d.com"]
     * - an associative map keyed by email with settings {"a@b.com": {"is_device_power_supply_active": true}, ...}
     *
     * In both cases, stores a flat list of emails on the device to maintain backward compatibility.
     *
     * @param Device $device The device to update
     * @param array|null $applicationEmail The application email data
     */
    public function updateApplicationEmails(Device $device, ?array $applicationEmail = [], ?array $settings = []): void
    {
        $result = [];
        foreach ($applicationEmail as $i => $email) {
            if (!empty($email)) {
                $result[$email] = $settings[$i];
            }
        }

        foreach ($result as $email => $settings) {
            $this->validator->validateEmail($email);
        }

        $device->setApplicationEmailList($result);
    }

    /**
     * Update application emails for a specific sensor (entry)
     * Accepts both flat list and associative mapping keyed by email, same as updateApplicationEmails().
     */
    public function updateApplicationEmailsForSensor(Device $device, int $sensor, ?array $applicationEmail = [], ?array $settings = []): void
    {
        $result = [];
        foreach ($applicationEmail as $i => $email) {
            if (!empty($email)) {
                $result[$email] = $settings[$i];
            }
        }

        foreach ($result as $email => $settings) {
            $this->validator->validateEmail($email);
        }

        $device->setEntryData($sensor, 'application_email', $result);
    }
}