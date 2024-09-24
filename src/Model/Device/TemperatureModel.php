<?php

namespace App\Model\Device;

class TemperatureModel
{
    private ?string $unit = null;
    private ?string $current = null;
    private ?string $minimum = null;
    private ?string $maximum = null;
    private ?string $average = null;
    private ?string $mean = null;
    private ?string $name = null;
    private ?string $location = null;
    private ?string $image = null;
    private bool $isShown = false;
    private bool $isUsed = false;
    private bool $isInOffset = false;

    public function getMinimumWithUnit(): string
    {
        return sprintf("%s %s", $this->minimum, $this->unit);
    }

    public function getMaximumWithUnit(): string
    {
        return sprintf("%s %s", $this->maximum, $this->unit);
    }

    public function getAverageWithUnit(): string
    {
        return sprintf("%s %s", $this->average, $this->unit);
    }

    public function getCurrentWithUnit(): string
    {
        return sprintf("%s %s", $this->current, $this->unit);
    }

    public function getMeanWithUnit(): string
    {
        return sprintf("%s %s", $this->mean, $this->unit);
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

    public function getMean(): ?string
    {
        return $this->mean;
    }

    public function setMean(?string $mean): self
    {
        $this->mean = $mean;
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

    public function getUnit(): ?string
    {
        return $this->unit;
    }

    public function setUnit(?string $unit): self
    {
        $this->unit = $unit;
        return $this;
    }

    public function getMinimum(): ?string
    {
        return $this->minimum;
    }

    public function setMinimum(?string $minimum): self
    {
        $this->minimum = $minimum;
        return $this;
    }

    public function getMaximum(): ?string
    {
        return $this->maximum;
    }

    public function setMaximum(?string $maximum): self
    {
        $this->maximum = $maximum;
        return $this;
    }

    public function getAverage(): ?string
    {
        return $this->average;
    }

    public function setAverage(?string $average): self
    {
        $this->average = $average;
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

    public function getIsInOffset(): bool
    {
        return $this->isInOffset;
    }

    public function setIsInOffset(bool $isInOffset): self
    {
        $this->isInOffset = $isInOffset;
        return $this;
    }

    public function getIsShown(): bool
    {
        return $this->isShown;
    }

    public function setIsShown(bool $isShown): self
    {
        $this->isShown = $isShown;
        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): self
    {
        $this->image = $image;
        return $this;
    }
}