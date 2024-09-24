<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\ClientStorageDeviceRepository;

#[ORM\Entity(repositoryClass: ClientStorageDeviceRepository::class)]
class ClientStorageDevice
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private $id;

    #[ORM\ManyToOne(targetEntity: ClientStorage::class, inversedBy: "deviceInput")]
    #[ORM\JoinColumn(nullable: false)]
    private ClientStorage $clientStorage;

    #[ORM\ManyToOne(targetEntity: Device::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Device $device;

    #[ORM\Column(type: "integer")]
    private $entry;

    #[ORM\Column(type: "integer")]
    private $fontSize;

    #[ORM\Column(type: "string", length: 7)]
    private $fontColor;

    #[ORM\Column(type: "integer")]
    private $positionX;

    #[ORM\Column(type: "integer")]
    private $positionY;

    #[ORM\Column(type: "string")]
    private $type;

    #[ORM\Column]
    private bool $backgroundActive = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getClientStorage(): ?ClientStorage
    {
        return $this->clientStorage;
    }

    public function setClientStorage(?ClientStorage $clientStorage): self
    {
        $this->clientStorage = $clientStorage;

        return $this;
    }

    public function getEntry(): ?int
    {
        return $this->entry;
    }

    public function setEntry(int $entry): self
    {
        $this->entry = $entry;

        return $this;
    }

    public function getFontSize(): ?int
    {
        return $this->fontSize;
    }

    public function setFontSize(int $fontSize): self
    {
        $this->fontSize = $fontSize;

        return $this;
    }

    public function getFontColor(): ?string
    {
        return $this->fontColor;
    }

    public function setFontColor(string $fontColor): self
    {
        $this->fontColor = $fontColor;

        return $this;
    }

    public function getPositionX(): ?int
    {
        return $this->positionX;
    }

    public function setPositionX(int $positionX): self
    {
        $this->positionX = $positionX;

        return $this;
    }

    public function getPositionY(): ?int
    {
        return $this->positionY;
    }

    public function setPositionY(int $positionY): self
    {
        $this->positionY = $positionY;

        return $this;
    }

    public function getDevice(): Device
    {
        return $this->device;
    }

    public function setDevice(Device $device): self
    {
        $this->device = $device;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function isBackgroundActive(): bool
    {
        return $this->backgroundActive;
    }

    public function setBackgroundActive(bool $backgroundActive): self
    {
        $this->backgroundActive = $backgroundActive;
        return $this;
    }
}
