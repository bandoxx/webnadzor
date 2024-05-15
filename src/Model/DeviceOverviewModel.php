<?php

namespace App\Model;

class DeviceOverviewModel
{
    private ?int $id = null;
    private ?int $entry = null;
    private ?string $location = null;
    private ?string $temperatureLocation = null;
    private ?string $relativeHumidityLocation = null;
    private ?string $name = null;
    private ?string $temperatureName = null;
    private ?string $relativeHumidityName = null;
    private ?bool $online = null;
    private ?bool $alarm = null;
    private ?bool $showTemperature = null;
    private ?bool $showHumidity = null;
    private ?string $temperatureUnit = null;
    private ?string $temperature = null;
    private ?string $temperatureMax = null;
    private ?string $temperatureMin = null;
    private ?string $temperatureAverage = null;
    private ?string $relativeHumidityUnit = null;
    private ?string $relativeHumidity = null;
    private ?string $meanKineticTemperature = null;
    private ?\DateTime $deviceDate = null;
    private ?string $temperatureImage = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getTemperatureName(): ?string
    {
        return $this->temperatureName;
    }

    public function setTemperatureName(?string $temperatureName): static
    {
        $this->temperatureName = $temperatureName;

        return $this;
    }

    public function setName(?string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getRelativeHumidityName(): ?string
    {
        return $this->relativeHumidityName;
    }

    public function setRelativeHumidityName(?string $relativeHumidityName): static
    {
        $this->relativeHumidityName = $relativeHumidityName;

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

    public function getEntry(): ?int
    {
        return $this->entry;
    }

    public function setEntry(?int $entry): static
    {
        $this->entry = $entry;

        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(?string $location): static
    {
        $this->location = $location;

        return $this;
    }

    public function getTemperature(): ?string
    {
        return $this->temperature;
    }

    public function setTemperature(?string $temperature): static
    {
        $this->temperature = $temperature;

        return $this;
    }

    public function getTemperatureMax(): ?string
    {
        return $this->temperatureMax;
    }

    public function setTemperatureMax(?string $temperatureMax): static
    {
        $this->temperatureMax = $temperatureMax;

        return $this;
    }

    public function getTemperatureMin(): ?string
    {
        return $this->temperatureMin;
    }

    public function setTemperatureMin(?string $temperatureMin): static
    {
        $this->temperatureMin = $temperatureMin;

        return $this;
    }

    public function getTemperatureAverage(): ?string
    {
        return $this->temperatureAverage;
    }

    public function setTemperatureAverage(?string $temperatureAverage): static
    {
        $this->temperatureAverage = $temperatureAverage;

        return $this;
    }

    public function getRelativeHumidity(): ?string
    {
        return $this->relativeHumidity;
    }

    public function setRelativeHumidity(?string $relativeHumidity): static
    {
        $this->relativeHumidity = $relativeHumidity;

        return $this;
    }

    public function getMeanKineticTemperature(): ?string
    {
        return $this->meanKineticTemperature;
    }

    public function setMeanKineticTemperature(?string $meanKineticTemperature): static
    {
        $this->meanKineticTemperature = $meanKineticTemperature;

        return $this;
    }

    public function getDeviceDate(): ?\DateTime
    {
        return $this->deviceDate;
    }

    public function setDeviceDate(?\DateTime $deviceDate): static
    {
        $this->deviceDate = $deviceDate;

        return $this;
    }

    public function getTemperatureImage(): ?string
    {
        return $this->temperatureImage;
    }

    public function setTemperatureImage(?string $temperatureImage): static
    {
        $this->temperatureImage = $temperatureImage;

        return $this;
    }

    public function getShowTemperature(): ?bool
    {
        return $this->showTemperature;
    }

    public function setShowTemperature(?bool $showTemperature): static
    {
        $this->showTemperature = $showTemperature;

        return $this;
    }

    public function getShowHumidity(): ?bool
    {
        return $this->showHumidity;
    }

    public function setShowHumidity(?bool $showHumidity): static
    {
        $this->showHumidity = $showHumidity;

        return $this;
    }

    public function getTemperatureUnit(): ?string
    {
        return $this->temperatureUnit;
    }

    public function setTemperatureUnit(?string $temperatureUnit): static
    {
        $this->temperatureUnit = $temperatureUnit;

        return $this;
    }

    public function getRelativeHumidityUnit(): ?string
    {
        return $this->relativeHumidityUnit;
    }

    public function setRelativeHumidityUnit(?string $relativeHumidityUnit): static
    {
        $this->relativeHumidityUnit = $relativeHumidityUnit;

        return $this;
    }

    public function getTemperatureLocation(): ?string
    {
        return $this->temperatureLocation;
    }

    public function setTemperatureLocation(?string $temperatureLocation): static
    {
        $this->temperatureLocation = $temperatureLocation;

        return $this;
    }

    public function getRelativeHumidityLocation(): ?string
    {
        return $this->relativeHumidityLocation;
    }

    public function setRelativeHumidityLocation(?string $relativeHumidityLocation): static
    {
        $this->relativeHumidityLocation = $relativeHumidityLocation;

        return $this;
    }
}