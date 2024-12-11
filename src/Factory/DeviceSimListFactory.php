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
        $model = $overview?->getTemperatureModel();
        if ($model?->getName() && $model?->getLocation()) {
            $address = sprintf("%s, %s", $model->getName(), $model->getLocation());
        } else {
            $overview = $this->deviceOverviewFactory->create($device, 2);
            $model = $overview?->getTemperatureModel();
            $address = sprintf("%s, %s", $model->getName(), $model->getLocation());
        }

        if ($address) {
            if (substr($address, -2) === ", ") {
                $address = substr($address, 0, -2);
            }
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