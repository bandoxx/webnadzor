<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: "App\Repository\ClientStorageDeviceRepository")]
#[ORM\Table(name: "client_storage_device")]
class ClientStorageDevice
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private $id;

    #[ORM\ManyToOne(targetEntity: "App\Entity\ClientStorage", inversedBy: "devices")]
    #[ORM\JoinColumn(nullable: false)]
    private $client_storage;

    #[ORM\Column(type: "integer")]
    private $device_id;

    #[ORM\Column(type: "string", length: 255)]
    private $entry;

    #[ORM\Column(type: "integer")]
    private $font_size;

    #[ORM\Column(type: "string", length: 7)]
    private $font_color;

    #[ORM\Column(type: "integer")]
    private $position_x;

    #[ORM\Column(type: "integer")]
    private $position_y;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getClientStorage(): ?ClientStorage
    {
        return $this->client_storage;
    }

    public function setClientStorage(?ClientStorage $client_storage): self
    {
        $this->client_storage = $client_storage;

        return $this;
    }

    public function getDeviceId(): ?int
    {
        return $this->device_id;
    }

    public function setDeviceId(int $device_id): self
    {
        $this->device_id = $device_id;

        return $this;
    }

    public function getEntry(): ?string
    {
        return $this->entry;
    }

    public function setEntry(string $entry): self
    {
        $this->entry = $entry;

        return $this;
    }

    public function getFontSize(): ?int
    {
        return $this->font_size;
    }

    public function setFontSize(int $font_size): self
    {
        $this->font_size = $font_size;

        return $this;
    }

    public function getFontColor(): ?string
    {
        return $this->font_color;
    }

    public function setFontColor(string $font_color): self
    {
        $this->font_color = $font_color;

        return $this;
    }

    public function getPositionX(): ?int
    {
        return $this->position_x;
    }

    public function setPositionX(int $position_x): self
    {
        $this->position_x = $position_x;

        return $this;
    }

    public function getPositionY(): ?int
    {
        return $this->position_y;
    }

    public function setPositionY(int $position_y): self
    {
        $this->position_y = $position_y;

        return $this;
    }
}
