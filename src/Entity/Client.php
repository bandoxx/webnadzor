<?php

namespace App\Entity;

use App\Repository\ClientRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ClientRepository::class)]
class Client
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToMany(targetEntity: User::class, mappedBy: 'client')]
    private Collection $user;

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
    private bool $mapActive = false;

    #[ORM\Column]
    private bool $temperatureActive = false;

    #[ORM\OneToOne(mappedBy: 'client', cascade: ['persist', 'remove'])]
    private ?ClientImage $clientImage = null;

    public function __construct()
    {
        $this->user = new ArrayCollection();
        $this->device = new ArrayCollection();
        $this->userDeviceAccesses = new ArrayCollection();
        $this->deviceIcons = new ArrayCollection();
        $this->loginLogArchives = new ArrayCollection();
        $this->loginLogs = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Collection<int, User>
     */
    public function getUser(): Collection
    {
        return $this->user;
    }

    public function addUser(User $user): static
    {
        if (!$this->user->contains($user)) {
            $this->user->add($user);
            $user->setClient($this);
        }

        return $this;
    }

    public function removeUser(User $user): static
    {
        if ($this->user->removeElement($user)) {
            // set the owning side to null (unless already changed)
            if ($user->getClient() === $this) {
                $user->setClient(null);
            }
        }

        return $this;
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

    public function isMapActive(): ?bool
    {
        return $this->mapActive;
    }

    public function setMapActive(bool $mapActive): static
    {
        $this->mapActive = $mapActive;

        return $this;
    }

    public function isTemperatureActive(): ?bool
    {
        return $this->temperatureActive;
    }

    public function setTemperatureActive(bool $temperatureActive): static
    {
        $this->temperatureActive = $temperatureActive;

        return $this;
    }

    public function getClientImage(): ?ClientImage
    {
        return $this->clientImage;
    }

    public function setClientImage(ClientImage $clientImage): static
    {
        // set the owning side of the relation if necessary
        if ($clientImage->getClient() !== $this) {
            $clientImage->setClient($this);
        }

        $this->clientImage = $clientImage;

        return $this;
    }
}
