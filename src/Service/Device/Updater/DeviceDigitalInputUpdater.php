<?php

namespace App\Service\Device\Updater;

use App\Entity\Device;
use App\Service\Device\Handler\DeviceImageHandler;
use App\Service\Device\Validator\DeviceDataValidator;

class DeviceDigitalInputUpdater
{
    public function __construct(
        private readonly DeviceImageHandler $imageHandler,
        private readonly DeviceDataValidator $validator
    ) {
    }

    /**
     * Update digital input configuration
     *
     * @param Device $device The device to update
     * @param int $entry The entry number
     * @param array $data The digital input data
     */
    public function updateDigitalInput(Device $device, int $entry, array $data): void
    {
        $dUse = $data['used'];
        $device->setEntryData($entry, 'd_use', $dUse);

        if ($dUse === false) {
            return;
        }

        $dName = trim($data['name']);
        $dOffName = trim($data['off_name']);
        $dOnName = trim($data['on_name']);

        if ($this->validator->validateLength($dName, 50)) {
            $device->setEntryData($entry, 'd_name', $dName);
        } else {
            $this->validator->addError('D Error');
        }

        if ($this->validator->validateLength($dOffName, 10) && $this->validator->validateLength($dOnName, 10)) {
            $device->setEntryData($entry, 'd_off_name', $dOffName);
            $device->setEntryData($entry, 'd_on_name', $dOnName);
        } else {
            $this->validator->addError('D on off name error');
        }

        $this->imageHandler->setImage($device, $entry, 'd_off_image', $data['off_image_id']);
        $this->imageHandler->setImage($device, $entry, 'd_on_image', $data['on_image_id']);
    }
}