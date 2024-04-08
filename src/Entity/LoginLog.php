<?php

namespace App\Entity;

use App\Repository\LoginLogRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LoginLogRepository::class)]
class LoginLog
{
    public const STATUS_OK = 1;
    public const STATUS_WRONG_USERNAME = 2;
    public const STATUS_WRONG_PASSWORD = 3;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'loginLogs')]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $user = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $serverDate = null;

    #[ORM\Column(nullable: true)]
    private ?int $status = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $username = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $password = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $ip = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $host = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $userAgent = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $os = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $browser = null;

    #[ORM\ManyToOne(inversedBy: 'loginLogs')]
    private ?Client $client = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getServerDate(): ?\DateTimeInterface
    {
        return $this->serverDate;
    }

    public function setServerDate(\DateTimeInterface $serverDate): static
    {
        $this->serverDate = $serverDate;

        return $this;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(?int $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(?string $username): static
    {
        $this->username = $username;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getIp(): ?string
    {
        return $this->ip;
    }

    public function setIp(?string $ip): static
    {
        $this->ip = $ip;

        return $this;
    }

    public function getHost(): ?string
    {
        return $this->host;
    }

    public function setHost(?string $host): static
    {
        $this->host = $host;

        return $this;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function setUserAgent(?string $userAgent): static
    {
        $this->userAgent = $userAgent;

        return $this;
    }

    public function getOs(): ?string
    {
        return $this->os;
    }

    public function setOs(?string $os): static
    {
        $this->os = $os;

        return $this;
    }

    public function getBrowser(): ?string
    {
        return $this->browser;
    }

    public function setBrowser(?string $browser): static
    {
        $this->browser = $browser;

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
}
