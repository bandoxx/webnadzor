<?php

namespace App\Entity;

use App\Repository\DeviceAlarmLogRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\HasLifecycleCallbacks]
#[ORM\Entity(repositoryClass: DeviceAlarmLogRepository::class)]
class DeviceAlarmLog
{

    public const string TYPE_EMAIL = 'email';
    public const string TYPE_PHONE_SMS   = 'phone-sms';
    public const string TYPE_PHONE_VOICE = 'phone-voice';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    private ?DeviceAlarm $deviceAlarm = null;

    #[ORM\ManyToOne]
    private ?Client $client = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $updatedAt;

    #[ORM\Column(length: 255)]
    private ?string $notifiedBy = null;

    #[ORM\Column]
    private ?string $recipient = null;

    #[ORM\Column]
    private ?string $message = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    #[ORM\PreUpdate]
    public function preUpdate(): void
    {
        $this->updatedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDeviceAlarm(): ?DeviceAlarm
    {
        return $this->deviceAlarm;
    }

    public function setDeviceAlarm(?DeviceAlarm $deviceAlarm): static
    {
        $this->deviceAlarm = $deviceAlarm;

        return $this;
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(?Client $client): static
    {
        $this->client = $client;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getNotifiedBy(): ?string
    {
        return $this->notifiedBy;
    }

    public function setNotifiedBy(string $notifiedBy): static
    {
        $this->notifiedBy = $notifiedBy;

        return $this;
    }

    public function getRecipient(): ?string
    {
        return $this->recipient;
    }

    public function setRecipient(?string $recipient): static
    {
        $this->recipient = $recipient;
        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): static
    {
        $this->message = $message;
        return $this;
    }
}
