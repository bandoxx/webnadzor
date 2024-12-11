<?php

namespace App\Model\Device;

class DeviceSimListItem
{

    private ?string $clientName = null;
    private ?string $xml = null;
    private ?string $address = null;
    private ?string $simNumber = null;
    private ?string $simProvider = null;

    public function getClientName(): ?string
    {
        return $this->clientName;
    }

    public function setClientName(?string $clientName): self
    {
        $this->clientName = $clientName;
        return $this;
    }

    public function getXml(): ?string
    {
        return $this->xml;
    }

    public function setXml(?string $xml): self
    {
        $this->xml = $xml;
        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): self
    {
        $this->address = $address;
        return $this;
    }

    public function getSimNumber(): ?string
    {
        return $this->simNumber;
    }

    public function setSimNumber(?string $simNumber): self
    {
        $this->simNumber = $simNumber;
        return $this;
    }

    public function getSimProvider(): ?string
    {
        return $this->simProvider;
    }

    public function setSimProvider(?string $simProvider): self
    {
        $this->simProvider = $simProvider;
        return $this;
    }
}