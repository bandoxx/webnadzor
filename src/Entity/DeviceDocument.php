<?php

namespace App\Entity;

use App\Repository\DeviceDocumentRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\HasLifecycleCallbacks]
#[ORM\Entity(repositoryClass: DeviceDocumentRepository::class)]
class DeviceDocument
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 4)]
    private ?string $year = null;

    #[ORM\Column(length: 255)]
    private ?string $numberOfDocument = null;

    #[ORM\Column(length: 255)]
    private ?string $serialSensorNumber = null;

    #[ORM\ManyToOne(inversedBy: 'deviceDocuments')]
    private ?Device $device = null;

    #[ORM\Column(nullable: true)]
    private ?int $entry = null;

    #[ORM\Column(length: 255)]
    private ?string $file = null;

    #[ORM\Column]
    private ?\DateTime $createdAt = null;

    #[ORM\Column]
    private ?\DateTime $updatedAt = null;

    #[ORM\PrePersist]
    public function prePersist()
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    #[ORM\PreUpdate]
    public function preUpdate()
    {
        $this->updatedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getYear(): ?string
    {
        return $this->year;
    }

    public function setYear(string $year): static
    {
        $this->year = $year;

        return $this;
    }

    public function getNumberOfDocument(): ?string
    {
        return $this->numberOfDocument;
    }

    public function setNumberOfDocument(string $numberOfDocument): static
    {
        $this->numberOfDocument = $numberOfDocument;

        return $this;
    }

    public function getSerialSensorNumber(): ?string
    {
        return $this->serialSensorNumber;
    }

    public function setSerialSensorNumber(string $serialSensorNumber): static
    {
        $this->serialSensorNumber = $serialSensorNumber;

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

    public function getEntry(): ?int
    {
        return $this->entry;
    }

    public function setEntry(?int $entry): static
    {
        $this->entry = $entry;

        return $this;
    }

    public function getFile(): ?string
    {
        return $this->file;
    }

    public function setFile(string $file): static
    {
        $this->file = $file;

        return $this;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTime $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}
