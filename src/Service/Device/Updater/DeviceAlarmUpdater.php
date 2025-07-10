<?php

namespace App\Service\Device\Updater;

use App\Entity\Device;
use App\Service\Device\Validator\DeviceDataValidator;
use Doctrine\ORM\EntityManagerInterface;

class DeviceAlarmUpdater
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly DeviceAlarmSetupEntryFactory $alarmSetupEntryFactory,
        private readonly DeviceAlarmSetupGeneralFactory $alarmSetupGeneralFactory,
        private readonly DeviceDataValidator $validator
    ) {
    }

    /**
     * Update general alarm setup configuration
     *
     * @param Device $device The device to update
     * @param array $data The alarm setup data
     */
    public function updateAlarmSetupGeneral(Device $device, array $data): void
    {
        $alarmSetupGenerals = $device->getDeviceAlarmSetupGenerals();

        foreach ($alarmSetupGenerals as $alarmSetupGeneral) {
            $this->entityManager->remove($alarmSetupGeneral);
        }

        foreach ($data as $generalEntry) {
            $phoneNumber = $generalEntry['phone_number'];

            $this->validator->validatePhoneNumber($phoneNumber);

            $prepare = $this->alarmSetupGeneralFactory->create(
                $device,
                $phoneNumber,
                $generalEntry['is_device_power_supply_active'],
                $generalEntry['is_sms_active'],
                $generalEntry['is_voice_message_active']
            );

            $this->entityManager->persist($prepare);
        }
    }

    /**
     * Update entry-specific alarm setup configuration
     *
     * @param Device $device The device to update
     * @param int $entry The entry number
     * @param array $data The alarm setup entry data
     */
    public function updateAlarmSetupEntry(Device $device, int $entry, array $data): void
    {
        foreach ($device->getDeviceAlarmSetupEntries() as $alarmSetupEntry) {
            $this->entityManager->remove($alarmSetupEntry);
        }

        foreach ($data as $entryData) {
            $phoneNumber = $entryData['phone_number'];

            if (empty($phoneNumber)) {
                continue;
            }

            $this->validator->validatePhoneNumber($phoneNumber);

            $prepare = $this->alarmSetupEntryFactory->create(
                $device,
                $entry,
                $phoneNumber,
                $entryData['is_digital_entry_active'],
                $entryData['digital_entry_alarm_value'],
                $entryData['is_humidity_active'],
                $entryData['is_temperature_active'],
                $entryData['is_sms_active'],
                $entryData['is_voice_message_active']
            );

            $this->entityManager->persist($prepare);
        }
    }
}