<?php

namespace App\Entity;

use App\Repository\AdminOverviewCacheRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AdminOverviewCacheRepository::class)]
#[ORM\Table(name: 'admin_overview_cache')]
class AdminOverviewCache
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: Client::class)]
    #[ORM\JoinColumn(name: 'client_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Client $client = null;

    #[ORM\Column(type: Types::INTEGER)]
    private int $numberOfDevices = 0;

    #[ORM\Column(type: Types::INTEGER)]
    private int $onlineDevices = 0;

    #[ORM\Column(type: Types::INTEGER)]
    private int $offlineDevices = 0;

    #[ORM\Column(type: Types::JSON)]
    private array $alarms = [];

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updatedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(Client $client): self
    {
        $this->client = $client;
        return $this;
    }

    public function getNumberOfDevices(): int
    {
        return $this->numberOfDevices;
    }

    public function setNumberOfDevices(int $numberOfDevices): self
    {
        $this->numberOfDevices = $numberOfDevices;
        return $this;
    }

    public function getOnlineDevices(): int
    {
        return $this->onlineDevices;
    }

    public function setOnlineDevices(int $onlineDevices): self
    {
        $this->onlineDevices = $onlineDevices;
        return $this;
    }

    public function getOfflineDevices(): int
    {
        return $this->offlineDevices;
    }

    public function setOfflineDevices(int $offlineDevices): self
    {
        $this->offlineDevices = $offlineDevices;
        return $this;
    }

    public function getAlarms(): array
    {
        return $this->alarms;
    }

    public function setAlarms(array $alarms): self
    {
        $this->alarms = $alarms;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }
}
