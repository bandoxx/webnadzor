<?php

namespace App\Model\Device;

class DeviceOverviewModel
{
    private ?int $id = null;
    private ?int $entry = null;
    private ?string $name = null;
    private ?string $note = null;
    private ?bool $online = null;
    private ?bool $alarm = null;
    private ?array $alarms = [];
    private ?float $power;
    private ?int $signal;
    private ?int $battery;
    private ?\DateTime $deviceDate = null;
    private ?TemperatureModel $temperatureModel = null;
    private ?HumidityModel $humidityModel = null;

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

    public function setName(?string $name): self
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

    public function getEntry(): ?int
    {
        return $this->entry;
    }

    public function setEntry(?int $entry): static
    {
        $this->entry = $entry;

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


    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): static
    {
        $this->note = $note;

        return $this;
    }

    public function getAlarms(): ?array
    {
        return $this->alarms;
    }

    public function setAlarms(?array $alarms): static
    {
        $this->alarms = $alarms;
        $this->alarm = false;

        if ($this->alarms) {
            $this->alarm = true;
        }

        return $this;
    }

    public function getPower(): ?float
    {
        return $this->power;
    }

    public function setPower(?float $power): self
    {
        $this->power = number_format($power, 1);
        return $this;
    }

    public function getSignal(): ?int
    {
        return $this->signal;
    }

    public function setSignal(?int $signal): self
    {
        $this->signal = $signal;
        return $this;
    }

    public function getBattery(): ?int
    {
        return $this->battery;
    }

    public function setBattery(?int $battery): self
    {
        $this->battery = $battery;
        return $this;
    }

    public function getTemperatureModel(): ?TemperatureModel
    {
        return $this->temperatureModel;
    }

    public function setTemperatureModel(?TemperatureModel $temperatureModel): self
    {
        $this->temperatureModel = $temperatureModel;
        return $this;
    }

    public function getHumidityModel(): ?HumidityModel
    {
        return $this->humidityModel;
    }

    public function setHumidityModel(?HumidityModel $humidityModel): self
    {
        $this->humidityModel = $humidityModel;
        return $this;
    }
}