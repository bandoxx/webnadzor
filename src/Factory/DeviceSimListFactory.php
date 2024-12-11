<?php

namespace App\Factory;

use App\Entity\Device;
use App\Model\Device\DeviceSimListItem;

class DeviceSimListFactory
{

    public function __construct(private DeviceOverviewFactory $deviceOverviewFactory) {}

    public function create(Device $device): DeviceSimListItem
    {
        $overview1 = $this->deviceOverviewFactory->create($device, 1);
        $overview2 = $this->deviceOverviewFactory->create($device, 2);

        $client = $device->getClient();
        return (new DeviceSimListItem())
            ->setXml($device->getXmlName())
            ->setClientName($client->getName())
            ->setAddress(rtrim(
                sprintf("%s, %s", $client->getAddress(),
                    $overview1->getTemperatureModel()->getName() ??
                    $overview2->getTemperatureModel()->getName() ?? null
                ),
            ',')
            )
            ->setSimNumber($device->getSimPhoneNumber())
            ->setSimProvider($device->getSimCardProvider())
        ;
    }

}