<?php

namespace App\Entity;

use App\Repository\DeviceAlarmRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DeviceAlarmRepository::class)]
class DeviceAlarm
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'deviceAlarms')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Device $device = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $serverDate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $deviceDate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $endServerDate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $endDeviceDate = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $sensor = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $type = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDevice(): ?Device
    {
        return $this->device;
    }

    public function setDevice(?Device $device): static
    {
        $this->device = $device;

        return $this;
    }

    public function getServerDate(): ?\DateTimeInterface
    {
        return $this->serverDate;
    }

    public function setServerDate(\DateTimeInterface $serverDate): static
    {
        $this->serverDate = $serverDate;

        return $this;
    }

    public function getDeviceDate(): ?\DateTimeInterface
    {
        return $this->deviceDate;
    }

    public function setDeviceDate(\DateTimeInterface $deviceDate): static
    {
        $this->deviceDate = $deviceDate;

        return $this;
    }

    public function getEndServerDate(): ?\DateTimeInterface
    {
        return $this->endServerDate;
    }

    public function setEndServerDate(?\DateTimeInterface $endServerDate): static
    {
        $this->endServerDate = $endServerDate;

        return $this;
    }

    public function getEndDeviceDate(): ?\DateTimeInterface
    {
        return $this->endDeviceDate;
    }

    public function setEndDeviceDate(?\DateTimeInterface $endDeviceDate): static
    {
        $this->endDeviceDate = $endDeviceDate;

        return $this;
    }

    public function getSensor(): ?string
    {
        return $this->sensor;
    }

    public function setSensor(?string $sensor): static
    {
        $this->sensor = $sensor;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): static
    {
        $this->type = $type;

        return $this;
    }
}
