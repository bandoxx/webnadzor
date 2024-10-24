<?php

namespace App\Entity;

use App\Repository\DeviceAlarmSetupEntryRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DeviceAlarmSetupEntryRepository::class)]
class DeviceAlarmSetupEntry
{
    use DeviceAlarmSetupTrait;

    #[ORM\Column]
    private ?int $entry = null;

    #[ORM\Column]
    private ?bool $isTemperatureActive = null;

    #[ORM\Column]
    private ?bool $isHumidityActive = null;

    #[ORM\Column]
    private ?bool $isDigitalEntryActive = null;

    #[ORM\Column]
    private ?bool $digitalEntryAlarmValue = null;

    #[ORM\PreUpdate]
    public function preUpdate(): void
    {
        $this->updatedAt = new \DateTime();
    }

    public function getEntry(): ?int
    {
        return $this->entry;
    }

    public function setEntry(int $entry): static
    {
        $this->entry = $entry;

        return $this;
    }

    public function isTemperatureActive(): ?bool
    {
        return $this->isTemperatureActive;
    }

    public function setTemperatureActive(bool $isTemperatureActive): static
    {
        $this->isTemperatureActive = $isTemperatureActive;

        return $this;
    }

    public function isHumidityActive(): ?bool
    {
        return $this->isHumidityActive;
    }

    public function setHumidityActive(bool $isHumidityActive): static
    {
        $this->isHumidityActive = $isHumidityActive;

        return $this;
    }

    public function isDigitalEntryActive(): ?bool
    {
        return $this->isDigitalEntryActive;
    }

    public function setDigitalEntryActive(bool $isDigitalEntryActive): static
    {
        $this->isDigitalEntryActive = $isDigitalEntryActive;

        return $this;
    }

    public function isDigitalEntryAlarmValue(): ?bool
    {
        return $this->digitalEntryAlarmValue;
    }

    public function setDigitalEntryAlarmValue(bool $digitalEntryAlarmValue): static
    {
        $this->digitalEntryAlarmValue = $digitalEntryAlarmValue;

        return $this;
    }
}
