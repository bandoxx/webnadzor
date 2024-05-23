<?php

namespace App\Service;

use App\Entity\Device;
use App\Repository\DeviceIconRepository;
use App\Repository\DeviceRepository;
use App\Service\XmlParser\DeviceSettingsMaker;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class DeviceUpdater
{

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly DeviceRepository       $deviceRepository,
        private readonly DeviceIconRepository   $deviceIconRepository,
        private readonly DeviceSettingsMaker    $deviceSettingsMaker,
        private readonly array                  $image = [],
        private array                           $error = []
    )
    {
    }

    public function update(Device $device, array $data): Device
    {
        $oldDevice = clone $device;

        ####### DEVICE NAME
        $deviceName = trim($data['device_name']);
        if ($this->length($deviceName, 40)) {
            $device->setName($deviceName);
        } else {
            $this->error[] = 'Device name too long.';
        }

        $xmlName = trim($data['xml_name']);
        if (!$this->deviceRepository->doesMoreThenOneXmlNameExists($xmlName)) {
            $device->setXmlName($xmlName);
        } else {
            $this->error[] = 'XML exists already.';
        }

        $location = trim($data['location']);
        if (preg_match('/^\d{1,4}(\.\d{1,6})?,\d{1,4}(\.\d{1,6})?$/', $location)) {
            $lat_lng = explode(',', $location);

            $device->setLatitude($lat_lng[0])
                ->setLongitude($lat_lng[1])
            ;
        }

        foreach (range(1, 2) as $entry) {
            $this->updateTemperature($device, $entry, $data);
            $this->updateHumidity($device, $entry, $data);
            $this->updateDigital($device, $entry, $data);
        }

        $device->setAlarmEmail(array_values(array_filter($data['smtp'] ?? [])));
        $device->setApplicationEmailList(array_values(array_filter($data['applicationEmail'] ?? [])));

        if ($this->error) {
            throw new BadRequestException(json_encode($this->error));
        }

        //$this->deviceSettingsMaker->saveXml($oldDevice, $data);
        $this->entityManager->flush();

        return $device;
    }

    private function updateTemperature(Device $device, int $entry, array $data): void
    {
        $tUse = (empty($data['t' . $entry . '_use'])) ? '0' : $data['t' . $entry . '_use'];
        $device->setEntryData($entry, 't_use', $tUse);
        $device->setEntryData($entry, 't_show_chart', (empty($data['t' . $entry . '_show_chart'])) ? '0' : $data['t' . $entry . '_show_chart']);

        $tLocation = trim($data['t' . $entry . '_location']);

        if ($this->length($tLocation, 50)) {
            $device->setEntryData($entry, 't_location', $tLocation);
        } else {
            $this->error[] = 'T location size.';
        }

        if ($tUse === '0') {
            return;
        }

        $tName = trim($data['t' . $entry . '_name']);
        $tUnit = trim($data['t' . $entry . '_unit']);

        if ($this->length($tName, 50)) {
            $device->setEntryData($entry, 't_name', $tName);
        } else {
            $this->error[] = 'T name size';
        }

        if ($this->length($tUnit, 8, 0)) {
            $device->setEntryData($entry, 't_unit', $tUnit);
        } else {
            $this->error[] = 'T unit length';
        }

        $tMin = trim($data['t' . $entry . '_min']);
        $tMax = trim($data['t' . $entry . '_max']);
        if ((!$tMin || preg_match('/^-?\d{1,2}(\.\d{1,2})?$/', $tMin)) && (!$tMax || preg_match('/^-?\d{1,2}(\.\d{1,2})?$/', $tMax))) {
            $device->setEntryData($entry, 't_min', $tMin);
            $device->setEntryData($entry, 't_max', $tMax);
        } else {
            $this->error[] = 'T min max error';
        }

        $this->setImage($device, $entry, 't_image', $data['t' . $entry . '_image']);
    }

    private function updateHumidity(Device $device, int $entry, array $data): void
    {
        $rhUse = (empty($data['rh' . $entry . '_use'])) ? '0' : $data['rh' . $entry . '_use'];
        $device->setEntryData($entry, 'rh_show_chart', (empty($data['rh' . $entry . '_show_chart'])) ? '0' : $data['rh' . $entry . '_show_chart']);
        $device->setEntryData($entry, 'rh_use', $rhUse);

        if ($rhUse === '0') {
            return;
        }

        $thName = trim($data['rh' . $entry . '_name']);
        $rhUnit = trim($data['rh' . $entry . '_unit']);

        if ($this->length($thName, 50)) {
            $device->setEntryData($entry, 'rh_name', $thName);
        } else {
            $this->error[] = 'RH name error';
        }

        if ($this->length($rhUnit, 8, 0)) {
            $device->setEntryData($entry, 'rh_unit', $rhUnit);
        } else {
            $this->error[] = 'RH Unit error';
        }

        $rhMin = trim($data['rh' . $entry . '_min']);
        $rhMax = trim($data['rh' . $entry . '_max']);
        if ((!$rhMin || preg_match('/^\d{1,2}(\.\d{1,2})?$/', $rhMin)) && (!$rhMax || preg_match('/^\d{1,2}(\.\d{1,2})?$/', $rhMax))) {
            $device->setEntryData($entry, 'rh_min', ($rhMin) ?: '');
            $device->setEntryData($entry, 'rh_max', ($rhMax) ?: '');
        } else {
            $this->error[] = 'RH min/max error';
        }

        $this->setImage($device, $entry, 'rh_image', $data['rh' . $entry . '_image']);
    }

    private function updateDigital(Device $device, int $entry, array $data): void
    {
        $dUse = (empty($data['d' . $entry . '_use'])) ? '0' : $data['d' . $entry . '_use'];
        $device->setEntryData($entry, 'd_use', $dUse);

        if ($dUse === '0') {
            return;
        }

        $dName = trim($data['d' . $entry . '_name']);
        $dOffName = trim($data['d' . $entry . '_off_name']);
        $dOnName = trim($data['d' . $entry . '_on_name']);

        if ($this->length($dName, 50)) {
            $device->setEntryData($entry, 'd_name', $dName);
        } else {
            $this->error[] = 'D Error';
        }

        if ($this->length($dOffName, 8) && $this->length($dOnName, 8)) {
            $device->setEntryData($entry, 'd_off_name', $dOffName);
            $device->setEntryData($entry, 'd_on_name', $dOnName);
        } else {
            $this->error[] = 'D on off name error';
        }

        $this->setImage($device, $entry, 'd_off_image', $data['d' . $entry . '_off_image']);
        $this->setImage($device, $entry, 'd_on_image', $data['d' . $entry . '_on_image']);
    }

    private function setImage(Device $device, int $entry, string $field, int $imageId): Device
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

        return $device;
    }

    private function length(string $string, int $max = 1, int $min = 1): bool
    {
        $length = strlen(utf8_decode($string));

        return $length >= $min && $length <= $max;
    }
}