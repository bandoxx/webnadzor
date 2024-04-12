<?php

namespace App\Entity;

use App\Repository\ClientImageRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ClientImageRepository::class)]
class ClientImage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'clientImage', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?Client $client = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $mainLogo = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $pdfLogo = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $mapMarkerIcon = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMainLogo(): ?string
    {
        return $this->mainLogo;
    }

    public function setMainLogo(?string $mainLogo): static
    {
        $this->mainLogo = $mainLogo;

        return $this;
    }

    public function getPdfLogo(): ?string
    {
        return $this->pdfLogo;
    }

    public function setPdfLogo(?string $pdfLogo): static
    {
        $this->pdfLogo = $pdfLogo;

        return $this;
    }

    public function getMapMarkerIcon(): ?string
    {
        return $this->mapMarkerIcon;
    }

    public function setMapMarkerIcon(?string $mapMarkerIcon): static
    {
        $this->mapMarkerIcon = $mapMarkerIcon;

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
}
