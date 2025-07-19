<?php

namespace App\Service;

use App\Entity\Device;
use App\Service\Device\Updater\DeviceAlarmUpdater;
use App\Service\Device\Updater\DeviceBasicInfoUpdater;
use App\Service\Device\Updater\DeviceDigitalInputUpdater;
use App\Service\Device\Updater\DeviceEmailConfigUpdater;
use App\Service\Device\Updater\DeviceSensorUpdater;
use App\Service\Device\Validator\DeviceDataValidator;
use App\Service\XmlParser\DeviceSettingsMaker;
use Doctrine\ORM\EntityManagerInterface;

class DeviceUpdater
{
    public function __construct(
        private readonly EntityManagerInterface         $entityManager,
        private readonly DeviceSettingsMaker            $deviceSettingsMaker,
        private readonly DeviceDataValidator            $validator,
        private readonly DeviceBasicInfoUpdater         $basicInfoUpdater,
        private readonly DeviceSensorUpdater            $sensorUpdater,
        private readonly DeviceDigitalInputUpdater      $digitalInputUpdater,
        private readonly DeviceAlarmUpdater             $alarmUpdater,
        private readonly DeviceEmailConfigUpdater       $emailConfigUpdater
    )
    {
    }

    public function update(Device $device, array $data): array
    {
        $oldDevice = clone $device;

        // Validate required fields
        if (empty($data['xml_name']) && empty($data['serial_number'])) {
            $this->validator->addError('Popunite bar jedno od dva polja da bi dodali lokaciju! (XML naziv ili Serijski broj)');
        }

        // Update basic device information
        $this->basicInfoUpdater->updateBasicInfo($device, $data);

        // Update email configurations
        $this->emailConfigUpdater->updateSmtpEmails($device, $data['smtp'] ?? []);
        $this->emailConfigUpdater->updateApplicationEmails($device, $data['device_email_alarm_setup_general']['application_email'] ?? []);

        // Update alarm configurations
        $this->alarmUpdater->updateAlarmSetupGeneral($device, $data['device_alarm_setup_general'] ?? []);

        // Update sensors and digital inputs for each entry
        foreach (range(1, 2) as $entry) {
            $this->sensorUpdater->updateTemperature($device, $entry, $data[sprintf("t%s", $entry)]);
            $this->sensorUpdater->updateHumidity($device, $entry, $data[sprintf('rh%s', $entry)]);
            $this->emailConfigUpdater->updateApplicationEmailsForSensor($device, $entry, $data[sprintf("device_email_alarm_setup_entry_%s", $entry)]['application_email']);
            $this->digitalInputUpdater->updateDigitalInput($device, $entry, $data[sprintf("d%s", $entry)]);
            $this->alarmUpdater->updateAlarmSetupEntry($device, $entry, $data[sprintf("device_alarm_setup_entry_%s", $entry)]);
        }

        // Check for validation errors
        if ($this->validator->hasErrors()) {
            return $this->validator->getErrors();
        }

        // Persist changes and save XML
        $this->entityManager->flush();
        $this->deviceSettingsMaker->saveXml($oldDevice, $data);

        return [];
    }

}
