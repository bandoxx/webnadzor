<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\ClientStorageTextRepository;

#[ORM\Entity(repositoryClass: ClientStorageTextRepository::class)]
class ClientStorageText
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private $id;

    #[ORM\ManyToOne(targetEntity: ClientStorage::class, inversedBy: "texts")]
    #[ORM\JoinColumn(nullable: false)]
    private $clientStorage;

    #[ORM\Column(type: "integer")]
    private $fontSize;

    #[ORM\Column(type: "string", length: 7)]
    private $fontColor;

    #[ORM\Column(type: "string", length: 255)]
    private $text;

    #[ORM\Column(type: "integer")]
    private $positionX;

    #[ORM\Column(type: "integer")]
    private $positionY;

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

    public function getPlaceholderText(): ?string
    {
        return $this->text;
    }

    public function setText(string $text): self
    {
        $this->text = $text;

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
}
