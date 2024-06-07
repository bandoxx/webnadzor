<?php

namespace App\Entity;

use App\Repository\ClientSettingRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ClientSettingRepository::class)]
class ClientSetting
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'clientSetting', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?Client $client = null;

    #[ORM\Column]
    private ?int $batteryLevelAlert = null;

    #[ORM\Column(type: Types::JSON)]
    private array $alarmNotificationList = [];

    #[ORM\Column]
    private ?bool $isBatteryLevelAlarmActive = null;

    #[ORM\Column]
    private ?int $deviceSignalAlarm = null;

    #[ORM\Column]
    private ?bool $isDeviceSignalAlarmActive = null;

    #[ORM\Column]
    private ?bool $isDeviceOfflineAlarmActive = null;

    #[ORM\Column]
    private ?bool $isDeviceSensorErrorAlarmActive = null;

    #[ORM\Column]
    private ?bool $isTemperatureAlarmActive = null;

    #[ORM\Column]
    private ?bool $isHumidityAlarmActive = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBatteryLevelAlert(): ?int
    {
        return $this->batteryLevelAlert;
    }

    public function setBatteryLevelAlert(int $batteryLevelAlert): static
    {
        $this->batteryLevelAlert = $batteryLevelAlert;

        return $this;
    }

    public function getAlarmNotificationList(): array
    {
        return $this->alarmNotificationList;
    }

    public function setAlarmNotificationList(array $alarmNotificationList): static
    {
        $this->alarmNotificationList = $alarmNotificationList;

        return $this;
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(Client $client): static
    {
        $this->client = $client;

        return $this;
    }

    public function isBatteryLevelAlarmActive(): ?bool
    {
        return $this->isBatteryLevelAlarmActive;
    }

    public function setBatteryLevelAlarmActive(bool $isBatteryLevelAlarmActive): static
    {
        $this->isBatteryLevelAlarmActive = $isBatteryLevelAlarmActive;

        return $this;
    }

    public function getDeviceSignalAlarm(): ?int
    {
        return $this->deviceSignalAlarm;
    }

    public function setDeviceSignalAlarm(int $deviceSignalAlarm): static
    {
        $this->deviceSignalAlarm = $deviceSignalAlarm;

        return $this;
    }

    public function isDeviceSignalAlarmActive(): ?bool
    {
        return $this->isDeviceSignalAlarmActive;
    }

    public function setDeviceSignalAlarmActive(bool $isDeviceSignalAlarmActive): static
    {
        $this->isDeviceSignalAlarmActive = $isDeviceSignalAlarmActive;

        return $this;
    }

    public function isDeviceOfflineAlarmActive(): ?bool
    {
        return $this->isDeviceOfflineAlarmActive;
    }

    public function setDeviceOfflineAlarmActive(bool $isDeviceOfflineAlarmActive): static
    {
        $this->isDeviceOfflineAlarmActive = $isDeviceOfflineAlarmActive;

        return $this;
    }

    public function isDeviceSensorErrorAlarmActive(): ?bool
    {
        return $this->isDeviceSensorErrorAlarmActive;
    }

    public function setDeviceSensorErrorAlarmActive(bool $isDeviceSensorErrorAlarmActive): static
    {
        $this->isDeviceSensorErrorAlarmActive = $isDeviceSensorErrorAlarmActive;

        return $this;
    }

    public function getIsTemperatureAlarmActive(): ?bool
    {
        return $this->isTemperatureAlarmActive;
    }

    public function setIsTemperatureAlarmActive(?bool $isTemperatureAlarmActive): static
    {
        $this->isTemperatureAlarmActive = $isTemperatureAlarmActive;
        return $this;
    }

    public function getIsHumidityAlarmActive(): ?bool
    {
        return $this->isHumidityAlarmActive;
    }

    public function setIsHumidityAlarmActive(?bool $isHumidityAlarmActive): static
    {
        $this->isHumidityAlarmActive = $isHumidityAlarmActive;
        return $this;
    }
}
