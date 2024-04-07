<?php

namespace App\Service;

use App\Entity\Device;
use App\Entity\User;
use App\Repository\DeviceIconRepository;
use App\Repository\DeviceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\Security\Core\Security;

class DeviceUpdater
{

    public function __construct(
        private EntityManagerInterface $entityManager,
        private DeviceRepository $deviceRepository,
        private Security $security,
        private DeviceIconRepository $deviceIconRepository,
        private array $image = [],
        private array $error = []
    )
    {
    }

    public function update(Device $device, array $data)
    {
        dump($device);
        /** @var User $user */
        $user = $this->security->getUser();

        if ($user->getPermission() <= 3) {
            return false;
        }

        ####### DEVICE NAME
        $deviceName = trim($data['device_name']);
        if ($this->length($deviceName, 40)) {
            $device->setName($deviceName);
        } else {
            $this->error[] = 'Device name too long.';
        }
        ###

        ####### XML NAME
        $xmlName = trim($data['xml_name']);
        if (!$this->deviceRepository->doesMoreThenOneXmlNameExists($xmlName)) {
            $device->setXmlName($xmlName);
        } else {
            $this->error[] = 'XML exists already.';
        }
        ###

        ####### GOOGLE CORDINATES
        $location = trim($data['location']);
        if (preg_match('/^\d{1,4}(\.\d{1,6})?,\d{1,4}(\.\d{1,6})?$/', $location)) {
            $lat_lng = explode(',', $location);

            $device->setLatitude($lat_lng[0])
                ->setLongitude($lat_lng[1])
            ;
        } else {
            $this->error[] = 'Wrong location';
        }
        ###

        foreach (range(1, 2) as $entry) {

            ####### TEMPERATURE INPUTS
            $tLocation = trim($data['t' . $entry . '_location']);
            $tName = trim($data['t' . $entry . '_name']);
            $tUnit = trim($data['t' . $entry . '_unit']);
            ### LOCATION
            if ($this->length($tLocation, 16)) {
                $device->setEntryData($entry, 't_location', $tLocation);
            } else {
                $this->error[] = 'T location size.';
            }
            ##
            ### NAME
            if ($this->length($tName, 30)) {
                $device->setEntryData($entry, 't_name', $tName);
            } else {
                $this->error[] = 'T name size';
            }
            ##
            ### UNIT
            if ($this->length($tUnit, 8, 0)) {
                $device->setEntryData($entry, 't_unit', $tUnit);
            } else {
                $this->error[] = 'T unit length';
            }
            ##

            ## ALARM TEMP. MIN/MAX
            $tMin = trim($data['t' . $entry . '_min']);
            $tMax = trim($data['t' . $entry . '_max']);
            if ((!$tMin || preg_match('/^-?\d{1,2}(\.\d{1,2})?$/', $tMin)) && (!$tMax || preg_match('/^-?\d{1,2}(\.\d{1,2})?$/', $tMax))) {
                $device->setEntryData($entry, 't_min', ($tMin) ?: '');
                $device->setEntryData($entry, 't_max', ($tMax) ?: '');
            } else {
                $this->error[] = 'T min max error';
            }
            ##

            ## IMAGE (ICON)
            $device = $this->setImage($device, $entry, 't_image', $data['t' . $entry . '_image']);
            ##

            ## USE ANALOG AND SHOW CHART
            $device->setEntryData($entry, 't_use', (empty($data['t' . $entry . '_use'])) ? '0' : '1');
            $device->setEntryData($entry, 't_show_chart', (empty($data['t' . $entry . '_show_chart'])) ? '0' : '1');
            ##

            #################

            ####### R. HUMIDITY INPUTS
            $thName = trim($data['rh' . $entry . '_name']);
            $rhUnit = trim($data['rh' . $entry . '_unit']);
            ### NAME
            if ($this->length($thName, 24)) {
                $device->setEntryData($entry, 'rh_name', $thName);
            } else {
                $this->error[] = 'RH name error';
            }
            ##
            ### UNIT
            if ($this->length($rhUnit, 8, 0)) {
                $device->setEntryData($entry, 'rh_unit', $rhUnit);
            } else {
                $this->error[] = 'RH Unit error';
            }
            ##

            ## ALARM R. HUMIDITY MIN/MAX
            $rhMin = trim($data['rh' . $entry . '_min']);
            $rhMax = trim($data['rh' . $entry . '_max']);
            if ((!$rhMin || preg_match('/^\d{1,2}(\.\d{1,2})?$/', $rhMin)) && (!$rhMax || preg_match('/^\d{1,2}(\.\d{1,2})?$/', $rhMax))) {
                $device->setEntryData($entry, 'rh_min', ($rhMin) ?: '');
                $device->setEntryData($entry, 'rh_max', ($rhMax) ?: '');
            } else {
                $this->error[] = 'RH min/max error';
            }
            ##

            $device = $this->setImage($device, $entry, 'rh_image', $data['rh' . $entry . '_image']);

            ## USE HUMIDITY AND SHOW CHART
            $device->setEntryData($entry, 'rh_use', (empty($data['rh' . $entry . '_use'])) ? '0' : '1');
            $device->setEntryData($entry, 'rh_show_chart', (empty($data['rh' . $entry . '_show_chart'])) ? '0' : '1');
            ##

            #################

            ####### DIGITAL INPUTS
            $dName = trim($data['d' . $entry . '_name']);
            $dOffName = trim($data['d' . $entry . '_off_name']);
            $dOnName = trim($data['d' . $entry . '_on_name']);
            ### NAME
            if ($this->length($dName, 24)) {
                $device->setEntryData($entry, 'd_name', $dName);
            } else {
                $this->error[] = 'D Error';
            }
            ##
            ### OFF/ON NAME
            if ($this->length($dOffName, 8) && $this->length($dOnName, 8)) {
                $device->setEntryData($entry, 'd_off_name', $dOffName);
                $device->setEntryData($entry, 'd_on_name', $dOnName);
            } else {
                $this->error[] = 'D on off name error';
            }
            ##

            ## IMAGE OFF/ON (ICON)
            $device = $this->setImage($device, $entry, 'd_off_image', $data['d' . $entry . '_off_image']);
            $device = $this->setImage($device, $entry, 'd_on_image', $data['d' . $entry . '_on_image']);
            ##

            ## USE DIGITAL
            $device->setEntryData($entry, 'd_use', (empty($data['d' . $entry . '_use'])) ? '0' : '1');
            ##
        }

        if ($this->error) {
            throw new BadRequestException(json_encode($this->error));
        }

        $this->entityManager->flush();

        return $device;
    }

    private function setImage(Device $device, $entry, $field, $imageId)
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

    private function length($string, $max = 1, $min = 1) {
        $string = strlen(utf8_decode($string));

        return $string >= (int)@$min && $string <= (int)@$max;
    }
}