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

    #[ORM\ManyToOne]
    private ?DeviceData $deviceData = null;

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

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $message = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $shortMessage = null;

    #[ORM\Column]
    private bool $isNotified = false;

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

    public function isIsNotified(): ?bool
    {
        return $this->isNotified;
    }

    public function setIsNotified(bool $isNotified): static
    {
        $this->isNotified = $isNotified;

        return $this;
    }

    public function getLocation()
    {
        if ($this->getSensor()) {
            return $this->getDevice()->getEntryData($this->getSensor())['t_name'];
        }

        return 'Nema';
    }

    public function isActive(): bool
    {
        return !((bool) $this->getEndDeviceDate());
    }

    public function getDeviceData(): ?DeviceData
    {
        return $this->deviceData;
    }

    public function setDeviceData(?DeviceData $deviceData): static
    {
        $this->deviceData = $deviceData;

        return $this;
    }

    public function getMessage(): ?string
    {
        // old messages didn't have this property
        if (empty($this->message)) {
            return sprintf("Mjerno mjesto: %s, Lokacija: %s, Tip alarma: '%s', upaljen od: %s",
                $this->device->getName(),
                $this->getLocation(),
                $this->getType(),
                $this->getDeviceDate()->format('d.m.Y H:i:s')
            );
        }

        return $this->message;
    }

    public function getTimeString(): string
    {
        return sprintf("Alarm aktiviran: %s", $this->getDeviceDate()->format('d.m.Y H:i:s'));
    }

    public function setMessage(?string $message): static
    {
        $this->message = $message;

        return $this;
    }

    public function getShortMessage(): ?string
    {
        return $this->shortMessage;
    }

    public function setShortMessage(?string $shortMessage): static
    {
        $this->shortMessage = $shortMessage;
        return $this;
    }
}
