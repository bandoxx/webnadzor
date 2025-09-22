<?php

namespace App\Entity;

use App\Repository\DeviceDataLastCacheRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DeviceDataLastCacheRepository::class)]
#[ORM\Table(name: 'device_data_last_cache')]
#[ORM\UniqueConstraint(name: 'uniq_device_entry', columns: ['device_id', 'entry'])]
class DeviceDataLastCache
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Device::class)]
    #[ORM\JoinColumn(name: 'device_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Device $device = null;

    #[ORM\Column(type: Types::SMALLINT)]
    private int $entry = 1; // 1 or 2

    #[ORM\ManyToOne(targetEntity: DeviceData::class)]
    #[ORM\JoinColumn(name: 'device_data_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?DeviceData $deviceData = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $deviceDate = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDevice(): ?Device
    {
        return $this->device;
    }

    public function setDevice(Device $device): self
    {
        $this->device = $device;
        return $this;
    }

    public function getEntry(): int
    {
        return $this->entry;
    }

    public function setEntry(int $entry): self
    {
        $this->entry = $entry;
        return $this;
    }

    public function getDeviceData(): ?DeviceData
    {
        return $this->deviceData;
    }

    public function setDeviceData(DeviceData $deviceData): self
    {
        $this->deviceData = $deviceData;
        return $this;
    }

    public function getDeviceDate(): ?\DateTimeInterface
    {
        return $this->deviceDate;
    }

    public function setDeviceDate(\DateTimeInterface $deviceDate): self
    {
        $this->deviceDate = $deviceDate;
        return $this;
    }
}
