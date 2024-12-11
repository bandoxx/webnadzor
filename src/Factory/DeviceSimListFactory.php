<?php

namespace App\Factory;

use App\Entity\Device;
use App\Model\Device\DeviceSimListItem;

class DeviceSimListFactory
{

    public function __construct(private DeviceOverviewFactory $deviceOverviewFactory) {}

    public function create(Device $device): DeviceSimListItem
    {
        $client = $device->getClient();
        $overview = $this->deviceOverviewFactory->create($device, 1);

        if ($overview->getTemperatureModel()->getLocation()) {
            $location = $overview->getTemperatureModel()->getLocation();
        } else {
            $overview = $this->deviceOverviewFactory->create($device, 2);

            $location = $overview->getTemperatureModel()->getLocation();
        }

        $address = sprintf("%s, %s", $client->getAddress(), $location);
        if (substr($address, -2) === ", ") {
            $address = substr($address, 0, -2);
        }

        return (new DeviceSimListItem())
            ->setXml($device->getXmlName())
            ->setClientName($client->getName())
            ->setAddress($address)
            ->setSimNumber($device->getSimPhoneNumber())
            ->setSimProvider($device->getSimCardProvider())
        ;
    }

}