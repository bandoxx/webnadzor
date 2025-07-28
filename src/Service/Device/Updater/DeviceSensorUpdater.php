<?php

namespace App\Service\Device\Updater;

use App\Entity\Device;
use App\Service\Device\Handler\DeviceImageHandler;
use App\Service\Device\Validator\DeviceDataValidator;

class DeviceSensorUpdater
{
    public function __construct(
        private readonly DeviceImageHandler $imageHandler,
        private readonly DeviceDataValidator $validator
    ) {
    }

    /**
     * Update temperature sensor configuration
     *
     * @param Device $device The device to update
     * @param int $entry The entry number
     * @param array $data The temperature data
     */
    public function updateTemperature(Device $device, int $entry, array $data): void
    {
        $isUsed = $data['used'];

        $device->setEntryData($entry, 't_use', $isUsed);
        $device->setEntryData($entry, 't_show_chart', $data['show_chart']);
        $device->setEntryData($entry, 't_location', trim($data['location']));

        if ($isUsed === false) {
            return;
        }

        $tName = trim($data['name']);

        if ($this->validator->validateLength($tName, 50)) {
            $device->setEntryData($entry, 't_name', $tName);
        } else {
            $this->validator->addError(sprintf('Naziv lokacije za temperaturu je predugačko'));
        }

        $tUnit = trim($data['unit']);

        if ($this->validator->validateLength($tUnit, 8)) {
            $device->setEntryData($entry, 't_unit', $tUnit);
        } else {
            $this->validator->addError('T unit length');
        }

        $tMin = trim($data['min']);
        $tMax = trim($data['max']);

        if ((!$tMin || preg_match('/^-?\d{1,2}(\.\d{1,2})?$/', $tMin)) && (!$tMax || preg_match('/^-?\d{1,2}(\.\d{1,2})?$/', $tMax))) {
            $device->setEntryData($entry, 't_min', $tMin);
            $device->setEntryData($entry, 't_max', $tMax);
        } else {
            $this->validator->addError('T min max error');
        }

        $chartMin = trim($data['chart_min']);
        $chartMax = trim($data['chart_max']);
        $device->setEntryData($entry, 't_chart_min', $chartMin);
        $device->setEntryData($entry, 't_chart_max', $chartMax);

        if (isset($data['note'])) {
            $device->setEntryData($entry, 't_note', trim($data['note']));
        }

        $this->imageHandler->setImage($device, $entry, 't_image', $data['image_id']);
    }

    /**
     * Update humidity sensor configuration
     *
     * @param Device $device The device to update
     * @param int $entry The entry number
     * @param array $data The humidity data
     */
    public function updateHumidity(Device $device, int $entry, array $data): void
    {
        $rhUse = $data['used'];
        $device->setEntryData($entry, 'rh_show_chart', $data['show_chart']);
        $device->setEntryData($entry, 'rh_use', $rhUse);

        if ($rhUse === false) {
            return;
        }

        $thName = trim($data['name']);
        $rhUnit = trim($data['unit']);

        if ($this->validator->validateLength($thName, 50)) {
            $device->setEntryData($entry, 'rh_name', $thName);
        } else {
            $this->validator->addError('Naziv lokacije za vlagu je predugačko.');
        }

        if ($this->validator->validateLength($rhUnit, 8)) {
            $device->setEntryData($entry, 'rh_unit', $rhUnit);
        } else {
            $this->validator->addError('RH Unit error');
        }

        $rhMin = trim($data['min']);
        $rhMax = trim($data['max']);
        if ((!$rhMin || preg_match('/^\d{1,2}(\.\d{1,2})?$/', $rhMin)) && (!$rhMax || preg_match('/^\d{1,2}(\.\d{1,2})?$/', $rhMax))) {
            $device->setEntryData($entry, 'rh_min', ($rhMin) ?: '');
            $device->setEntryData($entry, 'rh_max', ($rhMax) ?: '');
        } else {
            $this->validator->addError('RH min/max error');
        }

        $chartMin = trim($data['chart_min']);
        $chartMax = trim($data['chart_max']);

        $device->setEntryData($entry, 'rh_chart_min', $chartMin);
        $device->setEntryData($entry, 'rh_chart_max', $chartMax);

        $this->imageHandler->setImage($device, $entry, 'rh_image', $data['image_id']);
    }
}