<?php

namespace App\Service;

use App\Entity\Device;
use App\Repository\DeviceIconRepository;
use App\Repository\DeviceRepository;
use App\Service\Device\Updater\DeviceAlarmSetupGeneralFactory;
use App\Service\Device\Updater\DeviceAlarmSetupEntryFactory;
use App\Service\XmlParser\DeviceSettingsMaker;
use Doctrine\ORM\EntityManagerInterface;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberUtil;

class DeviceUpdater
{

    private PhoneNumberUtil $phoneUtil;

    public function __construct(
        private readonly EntityManagerInterface         $entityManager,
        private readonly DeviceRepository               $deviceRepository,
        private readonly DeviceIconRepository           $deviceIconRepository,
        private readonly DeviceAlarmSetupEntryFactory   $alarmSetupEntryFactory,
        private readonly DeviceAlarmSetupGeneralFactory $alarmSetupGeneralFactory,
        private readonly DeviceSettingsMaker            $deviceSettingsMaker,
        private readonly array                          $image = [],
        private array                                   $error = []
    )
    {
        $this->phoneUtil = PhoneNumberUtil::getInstance();
    }

    public function update(Device $device, array $data): array
    {
        $oldDevice = clone $device;

        ####### DEVICE NAME
        $deviceName = trim($data['device_name']);
        if ($this->length($deviceName, 40)) {
            $device->setName($deviceName);
        } else {
            $this->error[] = 'Naziv uređaja je predugačko';
        }

        $this->updateXmlName($device, trim($data['xml_name']));
        $this->updateLocationCoordinates($device, $data['location']);
        $this->updateSmtpEmails($device, $data['smtp'] ?? []);
        $this->updateApplicationEmails($device, $data['application_email'] ?? []);
        $this->updateAlarmSetupGeneral($device, $data['device_alarm_setup_general'] ?? []);

        foreach (range(1, 2) as $entry) {
            $this->updateTemperature($device, $entry, $data[sprintf("t%s", $entry)]);
            $this->updateHumidity($device, $entry, $data[sprintf('rh%s', $entry)]);
            $this->updateDigital($device, $entry, $data[sprintf("d%s", $entry)]);
            $this->updateAlarmSetupEntry($device, $entry, $data[sprintf("device_alarm_setup_entry_%s", $entry)]);
        }

        if ($this->error) {
            return $this->error;
        }

        $device->setSimCardProvider($data['sim_card_provider'] ?? null);
        $device->setSerialNumber($data['serial_number'] ?? null);
        $device->setSimPhoneNumber($data['sim_phone_number'] ?? null);

        $this->entityManager->flush();
        $this->deviceSettingsMaker->saveXml($oldDevice, $data);

        return [];
    }

    private function updateTemperature(Device $device, int $entry, array $data): void
    {
        $isUsed = $data['used'];

        $device->setEntryData($entry, 't_use', $isUsed);
        $device->setEntryData($entry, 't_show_chart', $data['show_chart']);
        $device->setEntryData($entry, 't_location', trim($data['location']));

        if ($isUsed === false) {
            return;
        }

        $tName = trim($data['name']);

        if ($this->length($tName, 50)) {
            $device->setEntryData($entry, 't_name', $tName);
        } else {
            $this->error[] = sprintf('Naziv lokacije za temperaturu je predugačko');
        }

        $tUnit = trim($data['unit']);

        if ($this->length($tUnit, 8)) {
            $device->setEntryData($entry, 't_unit', $tUnit);
        } else {
            $this->error[] = 'T unit length';
        }

        $tMin = trim($data['min']);
        $tMax = trim($data['max']);

        if ((!$tMin || preg_match('/^-?\d{1,2}(\.\d{1,2})?$/', $tMin)) && (!$tMax || preg_match('/^-?\d{1,2}(\.\d{1,2})?$/', $tMax))) {
            $device->setEntryData($entry, 't_min', $tMin);
            $device->setEntryData($entry, 't_max', $tMax);
        } else {
            $this->error[] = 'T min max error';
        }

        $chartMin = trim($data['chart_min']);
        $chartMax = trim($data['chart_max']);
        $device->setEntryData($entry, 't_chart_min', $chartMin);
        $device->setEntryData($entry, 't_chart_max', $chartMax);

        if (isset($data['note'])) {
            $device->setEntryData($entry, 't_note', trim($data['note']));
        }

        $this->setImage($device, $entry, 't_image', $data['image_id']);
    }

    private function updateHumidity(Device $device, int $entry, array $data): void
    {
        $rhUse = $data['used'];
        $device->setEntryData($entry, 'rh_show_chart', $data['show_chart']);
        $device->setEntryData($entry, 'rh_use', $rhUse);

        if ($rhUse === false) {
            return;
        }

        $thName = trim($data['name']);
        $rhUnit = trim($data['unit']);

        if ($this->length($thName, 50)) {
            $device->setEntryData($entry, 'rh_name', $thName);
        } else {
            $this->error[] = 'Naziv lokacije za vlagu je predugačko.';
        }

        if ($this->length($rhUnit, 8)) {
            $device->setEntryData($entry, 'rh_unit', $rhUnit);
        } else {
            $this->error[] = 'RH Unit error';
        }

        $rhMin = trim($data['min']);
        $rhMax = trim($data['max']);
        if ((!$rhMin || preg_match('/^\d{1,2}(\.\d{1,2})?$/', $rhMin)) && (!$rhMax || preg_match('/^\d{1,2}(\.\d{1,2})?$/', $rhMax))) {
            $device->setEntryData($entry, 'rh_min', ($rhMin) ?: '');
            $device->setEntryData($entry, 'rh_max', ($rhMax) ?: '');
        } else {
            $this->error[] = 'RH min/max error';
        }

        $chartMin = trim($data['chart_min']);
        $chartMax = trim($data['chart_max']);

        $device->setEntryData($entry, 'rh_chart_min', $chartMin);
        $device->setEntryData($entry, 'rh_chart_max', $chartMax);

        $this->setImage($device, $entry, 'rh_image', $data['image_id']);
    }

    private function updateDigital(Device $device, int $entry, array $data): void
    {
        $dUse = $data['used'];
        $device->setEntryData($entry, 'd_use', $dUse);

        if ($dUse === false) {
            return;
        }

        $dName = trim($data['name']);
        $dOffName = trim($data['off_name']);
        $dOnName = trim($data['on_name']);

        if ($this->length($dName, 50)) {
            $device->setEntryData($entry, 'd_name', $dName);
        } else {
            $this->error[] = 'D Error';
        }

        if ($this->length($dOffName, 10) && $this->length($dOnName, 10)) {
            $device->setEntryData($entry, 'd_off_name', $dOffName);
            $device->setEntryData($entry, 'd_on_name', $dOnName);
        } else {
            $this->error[] = 'D on off name error';
        }

        $this->setImage($device, $entry, 'd_off_image', $data['off_image_id']);
        $this->setImage($device, $entry, 'd_on_image', $data['on_image_id']);
    }

    private function setImage(Device $device, int $entry, string $field, int $imageId): void
    {
        if (empty($imageId)) {
            $device->setEntryData($entry, $field, null);
        } elseif (in_array($imageId, $this->image, true)) {
            $device->setEntryData($entry, $field, $imageId);
        } elseif ($this->deviceIconRepository->find($imageId)) {
            $device->setEntryData($entry, $field, $imageId);
        } else {
            throw new \Exception(sprintf("%s image error", $field));
        }
    }

    private function updateXmlName(Device $device, string $xmlName): void
    {
        if ($device->getXmlName() !== $xmlName) {
            if ($this->deviceRepository->doesMoreThenOneXmlNameExists($xmlName) === false) {
                $device->setXmlName($xmlName);
            } else {
                $this->error[] = sprintf('%s - XML naziv se već koristi.', $xmlName);
            }
        }
    }

    private function updateAlarmSetupGeneral(Device $device, array $data): void
    {
        $alarmSetupGenerals = $device->getDeviceAlarmSetupGenerals();

        foreach ($alarmSetupGenerals as $alarmSetupGeneral) {
            $this->entityManager->remove($alarmSetupGeneral);
        }

        foreach ($data as $generalEntry) {
            $phoneNumber = $generalEntry['phone_number'];

            $this->validatePhoneNumber($phoneNumber);

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

    private function updateAlarmSetupEntry(Device $device, int $entry, array $data): void
    {
        foreach ($device->getDeviceAlarmSetupEntries() as $alarmSetupEntry) {
            $this->entityManager->remove($alarmSetupEntry);
        }

        foreach ($data as $entryData) {
            $phoneNumber = $entryData['phone_number'];

            if (empty($phoneNumber)) {
                continue;
            }

            $this->validatePhoneNumber($phoneNumber);

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


    private function length(string $string, int $max = 1, int $min = 0): bool
    {
        $length = strlen(mb_convert_encoding($string, 'ISO-8859-1', 'UTF-8'));

        return $length >= $min && $length <= $max;
    }

    private function validateEmail(string $email): void
    {
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $this->error[] = sprintf("%s email nije validan.", $email);
        }
    }

    private function validatePhoneNumber(string $phoneNumber): void
    {
        try {
            $parsed = $this->phoneUtil->parse($phoneNumber, 'HR');
            if ($this->phoneUtil->isValidNumber($parsed) === false) {
                $this->error[] = sprintf("Broj %s nije validan", $phoneNumber);
            }
        } catch (NumberParseException $e) {
            $this->error[] = sprintf("Broj %s nije validan", $phoneNumber);
        }
    }

    private function updateLocationCoordinates(Device $device, string $location): void
    {
        $location = str_replace(' ', '', $location);

        if (preg_match('/^\d{1,4}(\.\d{1,6})?,\d{1,4}(\.\d{1,6})?$/', $location)) {
            [$latitude, $longitude] = explode(',', $location);
            $device->setLatitude($latitude)
                ->setLongitude($longitude)
            ;
        }
    }

    private function updateSmtpEmails(Device $device, ?array $smtp = []): void
    {
        $smtpEmails = array_values(array_unique(array_filter($smtp)));

        foreach ($smtpEmails as $email) {
            $this->validateEmail($email);
        }

        $device->setAlarmEmail($smtpEmails);
    }

    private function updateApplicationEmails(Device $device, ?array $applicationEmail = []): void
    {
        $applicationEmails = array_values(array_unique(array_filter($applicationEmail)));

        foreach ($applicationEmails as $email) {
            $this->validateEmail($email);
        }

        $device->setApplicationEmailList($applicationEmails);
    }
}