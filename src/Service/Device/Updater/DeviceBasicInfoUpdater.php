<?php

namespace App\Service\Device\Updater;

use App\Entity\Device;
use App\Repository\DeviceRepository;
use App\Service\Device\Validator\DeviceDataValidator;

class DeviceBasicInfoUpdater
{
    public function __construct(
        private readonly DeviceRepository $deviceRepository,
        private readonly DeviceDataValidator $validator
    ) {
    }

    /**
     * Update basic device information
     *
     * @param Device $device The device to update
     * @param array $data The data to update with
     * @return bool Whether the update was successful
     */
    public function updateBasicInfo(Device $device, array $data): bool
    {
        $this->updateDeviceName($device, $data['device_name'] ?? '');
        $this->updateXmlName($device, $data['xml_name'] ?? null);
        $this->updateSerialNumber($device, $data['serial_number'] ?? null);
        $this->updateLocationCoordinates($device, $data['location'] ?? '');
        
        // Update SIM card information
        $device->setSimCardProvider($data['sim_card_provider'] ?? null);
        $device->setSimPhoneNumber($data['sim_phone_number'] ?? null);
        
        return !$this->validator->hasErrors();
    }

    /**
     * Update the device name
     */
    private function updateDeviceName(Device $device, string $deviceName): void
    {
        $deviceName = trim($deviceName);
        if ($this->validator->validateLength($deviceName, 40)) {
            $device->setName($deviceName);
        } else {
            $this->validator->addError('Naziv uređaja je predugačko');
        }
    }

    /**
     * Update the XML name of the device
     */
    private function updateXmlName(Device $device, ?string $xmlName): void
    {
        if ($device->getXmlName() !== $xmlName) {
            if (empty($xmlName)) {
                $device->setXmlName(null);
                return;
            }

            $xmlName = trim($xmlName);
            if ($this->deviceRepository->doesMoreThenOneXmlNameExists($xmlName) === false) {
                $device->setXmlName($xmlName);
            } else {
                $this->validator->addError(sprintf('%s - XML naziv se već koristi.', $xmlName));
            }
        }
    }

    /**
     * Update the serial number of the device
     */
    private function updateSerialNumber(Device $device, ?string $serialNumber): void
    {
        if ($device->getSerialNumber() !== $serialNumber) {
            if (empty($serialNumber)) {
                $device->setSerialNumber(null);
                return;
            }

            $serialNumber = trim($serialNumber);
            if ($this->deviceRepository->doesMoreThanOneSerialNumberExists($serialNumber) === false) {
                $device->setSerialNumber($serialNumber);
            } else {
                $this->validator->addError(sprintf('%s - Serijski broj se već koristi.', $serialNumber));
            }
        }
    }

    /**
     * Update the location coordinates of the device
     */
    private function updateLocationCoordinates(Device $device, string $location): void
    {
        $location = str_replace(' ', '', $location);

        if (preg_match('/^\d{1,4}(\.\d{1,6})?,\d{1,4}(\.\d{1,6})?$/', $location)) {
            [$latitude, $longitude] = explode(',', $location);
            $device->setLatitude($latitude)
                ->setLongitude($longitude);
        }
    }
}