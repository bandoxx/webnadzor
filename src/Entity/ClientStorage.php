<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use DateTime;
use App\Repository\ClientStorageRepository;

#[ORM\Entity(repositoryClass: ClientStorageRepository::class)]
class ClientStorage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private $id;

    #[ORM\ManyToOne(inversedBy: 'clientStorages')]
    #[ORM\JoinColumn(nullable: false)]
    private Client $client;

    #[ORM\OneToMany(targetEntity: ClientStorageText::class, mappedBy: 'clientStorage')]
    private Collection $textInput;

    #[ORM\OneToMany(targetEntity: ClientStorageDevice::class, mappedBy: 'clientStorage')]
    private Collection $deviceInput;

    #[ORM\OneToMany(targetEntity: ClientStorageDigitalEntry::class, mappedBy: 'clientStorage')]
    private Collection $digitalEntryInput;

    #[ORM\Column(type: "string", length: 255)]
    private $image;

    #[ORM\Column(type: "string", length: 255)]
    private $name;

    #[ORM\Column(type: "datetime")]
    private $createdAt;

    #[ORM\Column(type: "datetime")]
    private $updatedAt;

    public function __construct()
    {
        $this->createdAt = new DateTime();
        $this->updatedAt = new DateTime();
        $this->textInput = new ArrayCollection();
        $this->deviceInput = new ArrayCollection();
        $this->digitalEntryInput = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(string $image): self
    {
        $this->image = $image;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

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

    public function getClient(): Client
    {
        return $this->client;
    }

    public function setClient(Client $client): self
    {
        $this->client = $client;

        return $this;
    }

    /**
     * @return Collection<int, ClientStorageText>
     */
    public function getTextInput(): Collection
    {
        return $this->textInput;
    }

    public function addTextInput(ClientStorageText $textInput): static
    {
        if (!$this->textInput->contains($textInput)) {
            $this->textInput->add($textInput);
            $textInput->setClientStorage($this);
        }

        return $this;
    }

    public function removeTextInput(ClientStorageText $textInput): static
    {
        if ($this->textInput->removeElement($textInput)) {
            // set the owning side to null (unless already changed)
            if ($textInput->getClientStorage() === $this) {
                $textInput->setClientStorage(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ClientStorageDevice>
     */
    public function getDeviceInput(): Collection
    {
        return $this->deviceInput;
    }

    public function addDeviceInput(ClientStorageDevice $deviceInput): static
    {
        if (!$this->deviceInput->contains($deviceInput)) {
            $this->deviceInput->add($deviceInput);
            $deviceInput->setClientStorage($this);
        }

        return $this;
    }

    public function removeDeviceInput(ClientStorageDevice $deviceInput): static
    {
        if ($this->deviceInput->removeElement($deviceInput)) {
            // set the owning side to null (unless already changed)
            if ($deviceInput->getClientStorage() === $this) {
                $deviceInput->setClientStorage(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ClientStorageDevice>
     */
    public function getDigitalEntryInput(): Collection
    {
        return $this->digitalEntryInput;
    }

    public function addDigitalEntryInput(ClientStorageDigitalEntry $digitalEntryInput): static
    {
        if (!$this->digitalEntryInput->contains($digitalEntryInput)) {
            $this->digitalEntryInput->add($digitalEntryInput);
            $digitalEntryInput->setClientStorage($this);
        }

        return $this;
    }

    public function removeDigitalEntryInput(ClientStorageDigitalEntry $digitalEntryInput): static
    {
        if ($this->digitalEntryInput->removeElement($digitalEntryInput)) {
            // set the owning side to null (unless already changed)
            if ($digitalEntryInput->getClientStorage() === $this) {
                $digitalEntryInput->setClientStorage(null);
            }
        }

        return $this;
    }
}
