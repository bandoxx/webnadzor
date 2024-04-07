<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    public const ROLE_MARSAL = 'Maršal';
    public const ROLE_ADMINISTRATOR = 'Administrator';
    public const ROLE_MODERATOR = 'Moderator';
    public const ROLE_USER = 'Korisnik';
    public const PERMISSIONS = [
        1 => self::ROLE_USER,
        2 => self::ROLE_MODERATOR,
        3 => self::ROLE_ADMINISTRATOR,
        4 => self::ROLE_MARSAL
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $username = null;

    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    private bool $fromOldSystem = false;

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\OneToMany(targetEntity: LoginLog::class, mappedBy: 'user')]
    private Collection $loginLogs;

    #[ORM\ManyToOne(inversedBy: 'user')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Client $client = null;

    #[ORM\Column]
    private ?int $permission = null;

    #[ORM\OneToMany(targetEntity: UserDeviceAccess::class, mappedBy: 'user')]
    private Collection $userDeviceAccesses;

    #[ORM\Column(nullable: true)]
    private ?int $oldId = null;

    private ?array $availableLocations = null;

    public function __construct()
    {
        $this->loginLogs = new ArrayCollection();
        $this->userDeviceAccesses = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->username;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        if ($this->permission === 4) {
            $roles[] = 'ROLE_MARSAL';
        }

        if ($this->permission === 3) {
            $roles[] = 'ROLE_ADMINISTRATOR';
        }

        if ($this->permission === 2) {
            $roles[] = 'ROLE_MODERATOR';
        }

        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function isFromOldSystem(): bool
    {
        return $this->fromOldSystem;
    }

    public function setFromOldSystem(bool $fromOldSystem): static
    {
        $this->fromOldSystem = $fromOldSystem;

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
            $loginLog->setUser($this);
        }

        return $this;
    }

    public function removeLoginLog(LoginLog $loginLog): static
    {
        if ($this->loginLogs->removeElement($loginLog)) {
            // set the owning side to null (unless already changed)
            if ($loginLog->getUser() === $this) {
                $loginLog->setUser(null);
            }
        }

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

    public function getPermission(): ?int
    {
        return $this->permission;
    }

    public function getPermissionName(): ?string
    {
        if (array_key_exists($this->permission, self::PERMISSIONS)) {
            return self::PERMISSIONS[$this->permission];
        }

        return null;
    }

    public function setPermission(int $permission): static
    {
        $this->permission = $permission;

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
            $userDeviceAccess->setUser($this);
        }

        return $this;
    }

    public function removeUserDeviceAccess(UserDeviceAccess $userDeviceAccess): static
    {
        if ($this->userDeviceAccesses->removeElement($userDeviceAccess)) {
            // set the owning side to null (unless already changed)
            if ($userDeviceAccess->getUser() === $this) {
                $userDeviceAccess->setUser(null);
            }
        }

        return $this;
    }

    public function getOldId(): ?int
    {
        return $this->oldId;
    }

    public function setOldId(?int $oldId): static
    {
        $this->oldId = $oldId;

        return $this;
    }

    public function getAvailableLocations(): ?array
    {
        return $this->availableLocations;
    }

    public function setAvailableLocations(?array $availableLocations): self
    {
        $this->availableLocations = $availableLocations;

        return $this;
    }
}
