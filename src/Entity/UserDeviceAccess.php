<?php

namespace App\Entity;

use App\Repository\UserDeviceAccessRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserDeviceAccessRepository::class)]
class UserDeviceAccess
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'userDeviceAccesses')]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'userDeviceAccesses')]
    private ?Device $device = null;

    #[ORM\Column(nullable: true)]
    private ?int $sensor = null;

    #[ORM\ManyToOne(inversedBy: 'userDeviceAccesses')]
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

    public function getDevice(): ?Device
    {
        return $this->device;
    }

    public function setDevice(?Device $device): static
    {
        $this->device = $device;

        return $this;
    }

    public function getSensor(): ?int
    {
        return $this->sensor;
    }

    public function setSensor(?int $sensor): static
    {
        $this->sensor = $sensor;

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
