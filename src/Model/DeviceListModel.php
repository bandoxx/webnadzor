<?php

namespace App\Model;

class DeviceListModel
{
    private ?int $id = null;
    private ?string $xml = null;
    private ?string $name = null;
    private ?bool $online = null;
    private ?bool $alarm = null;
    private ?int $signal = null;
    private ?string $power = null;
    private ?float $battery = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getXml(): ?string
    {
        return $this->xml;
    }

    public function setXml(?string $xml): static
    {
        $this->xml = $xml;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getOnline(): ?bool
    {
        return $this->online;
    }

    public function setOnline(?bool $online): static
    {
        $this->online = $online;

        return $this;
    }

    public function getAlarm(): ?bool
    {
        return $this->alarm;
    }

    public function setAlarm(?bool $alarm): static
    {
        $this->alarm = $alarm;

        return $this;
    }

    public function getSignal(): ?int
    {
        return $this->signal;
    }

    public function setSignal(?int $signal): static
    {
        $this->signal = $signal;

        return $this;
    }

    public function getPower(): ?string
    {
        return $this->power;
    }

    public function setPower(?string $power): static
    {
        $this->power = $power;

        return $this;
    }

    public function getBattery(): ?float
    {
        return $this->battery;
    }

    public function setBattery(?float $battery): static
    {
        $this->battery = $battery;

        return $this;
    }

}