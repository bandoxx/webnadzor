<?php

namespace App\Service\Device\Handler;

use App\Entity\Device;
use App\Repository\DeviceIconRepository;
use Exception;

class DeviceImageHandler
{
    private array $cachedImages = [];

    public function __construct(
        private readonly DeviceIconRepository $deviceIconRepository,
        array $image = []
    ) {
        $this->cachedImages = $image;
    }

    /**
     * Set an image for a specific device entry and field
     *
     * @param Device $device The device to update
     * @param int $entry The entry number
     * @param string $field The field name
     * @param int|null $imageId The image ID to set
     * @throws Exception If the image ID is invalid
     */
    public function setImage(Device $device, int $entry, string $field, ?int $imageId): void
    {
        if (empty($imageId)) {
            $device->setEntryData($entry, $field, null);
            return;
        }

        if (in_array($imageId, $this->cachedImages, true)) {
            $device->setEntryData($entry, $field, $imageId);
            return;
        }

        if ($this->deviceIconRepository->find($imageId)) {
            $this->cachedImages[] = $imageId;
            $device->setEntryData($entry, $field, $imageId);
            return;
        }

        throw new Exception(sprintf("%s image error", $field));
    }

    /**
     * Add images to the cache for faster validation
     *
     * @param array $images Array of image IDs
     */
    public function setCachedImages(array $images): void
    {
        $this->cachedImages = $images;
    }
}
