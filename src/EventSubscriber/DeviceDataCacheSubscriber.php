<?php

namespace App\EventSubscriber;

use App\Entity\Device;
use App\Entity\DeviceData;
use App\Entity\DeviceDataLastCache;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PostPersistEventArgs;

#[AsDoctrineListener(event: 'postPersist')]
class DeviceDataCacheSubscriber
{
    public function postPersist(PostPersistEventArgs $args): void
    {
        $entity = $args->getObject();
        if (!$entity instanceof DeviceData) {
            return;
        }

        $deviceData = $entity;
        $em = $args->getObjectManager();
        $device = $deviceData->getDevice();
        $date = $deviceData->getDeviceDate();

        foreach (Device::SENSOR_ENTRIES as $entry) {
            $value = $deviceData->getT($entry);
            if ($value === null) {
                continue;
            }

            $repo = $em->getRepository(DeviceDataLastCache::class);
            /** @var DeviceDataLastCache|null $existing */
            $existing = $repo->findOneBy(['device' => $device, 'entry' => $entry]);

            if ($existing) {
                // Replace only if newer or equal (to ensure latest wins)
                if ($existing->getDeviceDate() === null || $date >= $existing->getDeviceDate()) {
                    $existing
                        ->setDeviceData($deviceData)
                        ->setDeviceDate($date);
                }
            } else {
                $deviceRef = $em->getReference(Device::class, $device->getId());
                $cache = (new DeviceDataLastCache())
                    ->setDevice($deviceRef)
                    ->setEntry($entry)
                    ->setDeviceData($deviceData)
                    ->setDeviceDate($date);
                $em->persist($cache);
            }
        }

        // Flush within postPersist is acceptable here since DeviceData is already persisted
        $em->flush();
    }
}
