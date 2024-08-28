<?php

namespace App\Entity;

use App\Repository\ClientRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ClientRepository::class)]
class Client
{
    public const OVERVIEW_MAP = 1;
    public const OVERVIEW_THERMOMETER = 2;
    public const OVERVIEW_ICONS = 3;

    public const OVERVIEW_SCADA = 4;

    public const DEVICE_OVERVIEW_ICON = 1;
    public const DEVICE_OVERVIEW_DYNAMIC = 2;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\OneToMany(targetEntity: Device::class, mappedBy: 'client')]
    private Collection $device;

    #[ORM\OneToMany(targetEntity: UserDeviceAccess::class, mappedBy: 'client')]
    private Collection $userDeviceAccesses;

    #[ORM\OneToMany(targetEntity: DeviceIcon::class, mappedBy: 'client')]
    private Collection $deviceIcons;

    #[ORM\OneToMany(targetEntity: LoginLogArchive::class, mappedBy: 'client')]
    private Collection $loginLogArchives;

    #[ORM\OneToMany(targetEntity: LoginLog::class, mappedBy: 'client')]
    private Collection $loginLogs;

    #[ORM\Column]
    private int $overviewViews = self::DEVICE_OVERVIEW_ICON;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $mainLogo = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $pdfLogo = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $mapMarkerIcon = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $oldDatabaseName = null;

    #[ORM\Column]
    private int $devicePageView = self::OVERVIEW_MAP;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $address = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $OIB = null;

    #[ORM\OneToOne(mappedBy: 'client', cascade: ['persist', 'remove'])]
    private ?ClientSetting $clientSetting = null;

    #[ORM\Column]
    private bool $isDeleted = false;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $deletedByUser = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $deletedAt = null;

    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class, mappedBy: 'clients')]
    private Collection $users;

    /**
     * @var Collection<int, ClientStorage>
     */
    #[ORM\OneToMany(targetEntity: ClientStorage::class, mappedBy: 'client')]
    private Collection $clientStorages;

    public function __construct()
    {
        $this->device = new ArrayCollection();
        $this->userDeviceAccesses = new ArrayCollection();
        $this->deviceIcons = new ArrayCollection();
        $this->loginLogArchives = new ArrayCollection();
        $this->loginLogs = new ArrayCollection();
        $this->users = new ArrayCollection();
        $this->clientStorages = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Collection<int, Device>
     */
    public function getDevice(): Collection
    {
        return $this->device;
    }

    public function addDevice(Device $device): static
    {
        if (!$this->device->contains($device)) {
            $this->device->add($device);
            $device->setClient($this);
        }

        return $this;
    }

    public function removeDevice(Device $device): static
    {
        if ($this->device->removeElement($device)) {
            // set the owning side to null (unless already changed)
            if ($device->getClient() === $this) {
                $device->setClient(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, UserDeviceAccess>
     */
    public function getUserDeviceAccesses(): Collection
    {
        return $this->userDeviceAccesses;
    }

    public function addUserDeviceAccess(UserDeviceAccess $userDeviceAccess): static
    {
        if (!$this->userDeviceAccesses->contains($userDeviceAccess)) {
            $this->userDeviceAccesses->add($userDeviceAccess);
            $userDeviceAccess->setClient($this);
        }

        return $this;
    }

    public function removeUserDeviceAccess(UserDeviceAccess $userDeviceAccess): static
    {
        if ($this->userDeviceAccesses->removeElement($userDeviceAccess)) {
            // set the owning side to null (unless already changed)
            if ($userDeviceAccess->getClient() === $this) {
                $userDeviceAccess->setClient(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, DeviceIcon>
     */
    public function getDeviceIcons(): Collection
    {
        return $this->deviceIcons;
    }

    public function addDeviceIcon(DeviceIcon $deviceIcon): static
    {
        if (!$this->deviceIcons->contains($deviceIcon)) {
            $this->deviceIcons->add($deviceIcon);
            $deviceIcon->setClient($this);
        }

        return $this;
    }

    public function removeDeviceIcon(DeviceIcon $deviceIcon): static
    {
        if ($this->deviceIcons->removeElement($deviceIcon)) {
            // set the owning side to null (unless already changed)
            if ($deviceIcon->getClient() === $this) {
                $deviceIcon->setClient(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, LoginLogArchive>
     */
    public function getLoginLogArchives(): Collection
    {
        return $this->loginLogArchives;
    }

    public function addLoginLogArchive(LoginLogArchive $loginLogArchive): static
    {
        if (!$this->loginLogArchives->contains($loginLogArchive)) {
            $this->loginLogArchives->add($loginLogArchive);
            $loginLogArchive->setClient($this);
        }

        return $this;
    }

    public function removeLoginLogArchive(LoginLogArchive $loginLogArchive): static
    {
        if ($this->loginLogArchives->removeElement($loginLogArchive)) {
            // set the owning side to null (unless already changed)
            if ($loginLogArchive->getClient() === $this) {
                $loginLogArchive->setClient(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, LoginLog>
     */
    public function getLoginLogs(): Collection
    {
        return $this->loginLogs;
    }

    public function addLoginLog(LoginLog $loginLog): static
    {
        if (!$this->loginLogs->contains($loginLog)) {
            $this->loginLogs->add($loginLog);
            $loginLog->setClient($this);
        }

        return $this;
    }

    public function removeLoginLog(LoginLog $loginLog): static
    {
        if ($this->loginLogs->removeElement($loginLog)) {
            // set the owning side to null (unless already changed)
            if ($loginLog->getClient() === $this) {
                $loginLog->setClient(null);
            }
        }

        return $this;
    }

    public function getOverviewViews(): int
    {
        return $this->overviewViews;
    }

    public function setOverviewViews(int $overviewViews): static
    {
        $this->overviewViews = $overviewViews;

        return $this;
    }

    public function getMainLogo(): ?string
    {
        return $this->mainLogo;
    }

    public function setMainLogo(?string $mainLogo): void
    {
        $this->mainLogo = $mainLogo;
    }

    public function getPdfLogo(): ?string
    {
        return $this->pdfLogo;
    }

    public function setPdfLogo(?string $pdfLogo): void
    {
        $this->pdfLogo = $pdfLogo;
    }

    public function getMapMarkerIcon(): ?string
    {
        return $this->mapMarkerIcon;
    }

    public function setMapMarkerIcon(?string $mapMarkerIcon): void
    {
        $this->mapMarkerIcon = $mapMarkerIcon;
    }

    public function getOldDatabaseName(): ?string
    {
        return $this->oldDatabaseName;
    }

    public function setOldDatabaseName(?string $oldDatabaseName): static
    {
        $this->oldDatabaseName = $oldDatabaseName;

        return $this;
    }

    public function getDevicePageView(): ?int
    {
        return $this->devicePageView;
    }

    public function setDevicePageView(int $devicePageView): static
    {
        $this->devicePageView = $devicePageView;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): static
    {
        $this->address = $address;

        return $this;
    }

    public function getOIB(): ?string
    {
        return $this->OIB;
    }

    public function setOIB(?string $OIB): static
    {
        $this->OIB = $OIB;

        return $this;
    }

    public function getHeader(): string
    {
        $parts = [];

        if ($this->getName()) {
            $parts[] = $this->getName();
        }

        if ($this->getAddress()) {
            $parts[] = $this->getAddress();
        }

        if ($this->getOIB()) {
            $parts[] = sprintf("OIB: %s", $this->getOIB());
        }

        return implode(', ', $parts);
    }

    public function getClientSetting(): ?ClientSetting
    {
        return $this->clientSetting;
    }

    public function setClientSetting(ClientSetting $clientSetting): static
    {
        // set the owning side of the relation if necessary
        if ($clientSetting->getClient() !== $this) {
            $clientSetting->setClient($this);
        }

        $this->clientSetting = $clientSetting;

        return $this;
    }

    public function isDeleted(): bool
    {
        return $this->isDeleted;
    }

    public function setDeleted(bool $isDeleted): static
    {
        $this->isDeleted = $isDeleted;

        return $this;
    }

    public function getDeletedByUser(): ?User
    {
        return $this->deletedByUser;
    }

    public function setDeletedByUser(?User $deletedByUser): static
    {
        $this->deletedByUser = $deletedByUser;

        return $this;
    }

    public function getDeletedAt(): ?\DateTimeInterface
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?\DateTimeInterface $deletedAt): static
    {
        $this->deletedAt = $deletedAt;

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    /**
     * @return Collection<int, ClientStorage>
     */
    public function getClientStorages(): Collection
    {
        return $this->clientStorages;
    }

    public function addClientStorage(ClientStorage $clientStorage): static
    {
        if (!$this->clientStorages->contains($clientStorage)) {
            $this->clientStorages->add($clientStorage);
            $clientStorage->setClient($this);
        }

        return $this;
    }

    public function removeClientStorage(ClientStorage $clientStorage): static
    {
        if ($this->clientStorages->removeElement($clientStorage)) {
            // set the owning side to null (unless already changed)
            if ($clientStorage->getClient() === $this) {
                $clientStorage->setClient(null);
            }
        }

        return $this;
    }
}
