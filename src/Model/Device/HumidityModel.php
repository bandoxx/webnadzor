<?php

namespace App\Model\Device;

class HumidityModel
{

    private ?string $unit = null;
    private ?string $current = null;
    private ?string $name = null;
    private ?string $location = null;
    private bool $isShown = false;
    private bool $isUsed = false;
    private bool $isInOffset = false;

    public function getCurrentWithUnit(): string
    {
        return sprintf("%s %s", $this->current, $this->unit);
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(?string $location): self
    {
        $this->location = $location;
        return $this;
    }

    public function getUnit(): ?string
    {
        return $this->unit;
    }

    public function setUnit(?string $unit): self
    {
        $this->unit = $unit;
        return $this;
    }

    public function getCurrent(): ?string
    {
        return $this->current;
    }

    public function setCurrent(?string $current): self
    {
        $this->current = $current;
        return $this;
    }

    public function isShown(): bool
    {
        return $this->isShown;
    }

    public function setIsShown(bool $isShown): self
    {
        $this->isShown = $isShown;
        return $this;
    }

    public function isUsed(): bool
    {
        return $this->isUsed;
    }

    public function setIsUsed(bool $isUsed): self
    {
        $this->isUsed = $isUsed;
        return $this;
    }

    public function isInOffset(): bool
    {
        return $this->isInOffset;
    }

    public function setIsInOffset(bool $isInOffset): self
    {
        $this->isInOffset = $isInOffset;
        return $this;
    }


}