<?php

namespace App\Entity;

use App\Repository\DeviceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DeviceRepository::class)]
class Device
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'device')]
    private ?Client $client = null;

    #[ORM\Column(length: 255)]
    private ?string $xmlName = null;

    #[ORM\Column]
    private ?bool $parserActive = true;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $name = null;

    #[ORM\Column(nullable: true)]
    private ?float $latitude = null;

    #[ORM\Column(nullable: true)]
    private ?float $longitude = null;

    #[ORM\OneToMany(targetEntity: DeviceData::class, mappedBy: 'device')]
    private Collection $deviceData;

    #[ORM\Column(nullable: true)]
    private ?array $entry1 = null;

    #[ORM\Column(nullable: true)]
    private ?array $entry2 = null;

    #[ORM\OneToMany(targetEntity: DeviceAlarm::class, mappedBy: 'device')]
    private Collection $deviceAlarms;

    #[ORM\OneToMany(targetEntity: UserDeviceAccess::class, mappedBy: 'device')]
    private Collection $userDeviceAccesses;

    #[ORM\Column(nullable: true)]
    private ?int $oldId = null;

    #[ORM\OneToMany(targetEntity: DeviceDataArchive::class, mappedBy: 'device')]
    private Collection $deviceDataArchives;

    #[ORM\Column(nullable: true)]
    private ?array $alarmEmail = null;

    #[ORM\Column]
    private ?int $xmlInterval = 0;

    #[ORM\Column(nullable: true)]
    private ?array $applicationEmailList = [];

    #[ORM\Column]
    private ?bool $isDeleted = false;

    /**
     * @var Collection<int, DeviceAlarmSetupGeneral>
     */
    #[ORM\OneToMany(targetEntity: DeviceAlarmSetupGeneral::class, mappedBy: 'device', orphanRemoval: true)]
    private Collection $deviceAlarmSetupGenerals;

    /**
     * @var Collection<int, DeviceAlarmSetupEntry>
     */
    #[ORM\OneToMany(targetEntity: DeviceAlarmSetupEntry::class, mappedBy: 'device', orphanRemoval: true)]
    private Collection $deviceAlarmSetupEntries;

    public function __construct()
    {
        $this->deviceData = new ArrayCollection();
        $this->deviceAlarms = new ArrayCollection();
        $this->userDeviceAccesses = new ArrayCollection();
        $this->deviceDataArchives = new ArrayCollection();
        $this->deviceAlarmSetupGenerals = new ArrayCollection();
        $this->deviceAlarmSetupEntries = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getXmlName(): ?string
    {
        return $this->xmlName;
    }

    public function setXmlName(string $xmlName): static
    {
        $this->xmlName = $xmlName;

        return $this;
    }

    public function isParserActive(): ?bool
    {
        return $this->parserActive;
    }

    public function setParserActive(bool $parserActive): static
    {
        $this->parserActive = $parserActive;

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

    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    public function setLatitude(?float $latitude): static
    {
        $this->latitude = $latitude;

        return $this;
    }

    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    public function setLongitude(?float $longitude): static
    {
        $this->longitude = $longitude;

        return $this;
    }

    /**
     * @return Collection<int, DeviceData>
     */
    public function getDeviceData(): Collection
    {
        return $this->deviceData;
    }

    public function addDeviceData(DeviceData $deviceData): static
    {
        if (!$this->deviceData->contains($deviceData)) {
            $this->deviceData->add($deviceData);
            $deviceData->setDevice($this);
        }

        return $this;
    }

    public function removeDeviceData(DeviceData $deviceData): static
    {
        if ($this->deviceData->removeElement($deviceData)) {
            // set the owning side to null (unless already changed)
            if ($deviceData->getDevice() === $this) {
                $deviceData->setDevice(null);
            }
        }

        return $this;
    }

    public function getEntry1(): ?array
    {
        return $this->entry1;
    }

    public function setEntry1(?array $entry1): static
    {
        $this->entry1 = $entry1;

        return $this;
    }

    public function getEntry2(): ?array
    {
        return $this->entry2;
    }

    public function setEntry2(?array $entry2): static
    {
        $this->entry2 = $entry2;

        return $this;
    }

    /**
     * @return Collection<int, DeviceAlarm>
     */
    public function getDeviceAlarms(): Collection
    {
        return $this->deviceAlarms;
    }

    public function addDeviceAlarm(DeviceAlarm $deviceAlarm): static
    {
        if (!$this->deviceAlarms->contains($deviceAlarm)) {
            $this->deviceAlarms->add($deviceAlarm);
            $deviceAlarm->setDevice($this);
        }

        return $this;
    }

    public function removeDeviceAlarm(DeviceAlarm $deviceAlarm): static
    {
        if ($this->deviceAlarms->removeElement($deviceAlarm)) {
            // set the owning side to null (unless already changed)
            if ($deviceAlarm->getDevice() === $this) {
                $deviceAlarm->setDevice(null);
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
            $userDeviceAccess->setDevice($this);
        }

        return $this;
    }

    public function removeUserDeviceAccess(UserDeviceAccess $userDeviceAccess): static
    {
        if ($this->userDeviceAccesses->removeElement($userDeviceAccess)) {
            // set the owning side to null (unless already changed)
            if ($userDeviceAccess->getDevice() === $this) {
                $userDeviceAccess->setDevice(null);
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

    /**
     * @return Collection<int, DeviceDataArchive>
     */
    public function getDeviceDataArchives(): Collection
    {
        return $this->deviceDataArchives;
    }

    public function addDeviceDataArchive(DeviceDataArchive $deviceDataArchive): static
    {
        if (!$this->deviceDataArchives->contains($deviceDataArchive)) {
            $this->deviceDataArchives->add($deviceDataArchive);
            $deviceDataArchive->setDevice($this);
        }

        return $this;
    }

    public function removeDeviceDataArchive(DeviceDataArchive $deviceDataArchive): static
    {
        if ($this->deviceDataArchives->removeElement($deviceDataArchive)) {
            // set the owning side to null (unless already changed)
            if ($deviceDataArchive->getDevice() === $this) {
                $deviceDataArchive->setDevice(null);
            }
        }

        return $this;
    }

    public function isTUsed($entry): bool
    {
        return (bool) $this->getEntryData($entry)['t_use'];
    }

    public function isRhUsed($entry): bool
    {
        return (bool) $this->getEntryData($entry)['rh_use'];
    }

    public function isDUsed($entry): bool
    {
        return (bool) $this->getEntryData($entry)['d_use'];
    }

    public function getEntryData($entry)
    {
        $getter = "getEntry$entry";

        return $this->$getter();
    }

    public function setEntryData($entry, $key, $value): static
    {
        $data = $this->getEntryData($entry);

        $data[$key] = $value;

        $setter = "setEntry$entry";

        $this->$setter($data);

        return $this;
    }

    public function getAlarmEmail(): ?array
    {
        return $this->alarmEmail;
    }

    public function setAlarmEmail(?array $alarmEmail): static
    {
        $this->alarmEmail = $alarmEmail;

        return $this;
    }

    public function getXmlInterval(): ?int
    {
        return $this->xmlInterval;
    }

    public function getXmlIntervalInSeconds(): ?int
    {
        return $this->xmlInterval * 60 + 8 * 60; // 8 minutes threshold
    }

    public function setXmlInterval(int $xmlInterval): static
    {
        $this->xmlInterval = $xmlInterval;

        return $this;
    }

    public function getApplicationEmailList(): ?array
    {
        return $this->applicationEmailList ?? [];
    }

    public function setApplicationEmailList(?array $applicationEmailList): static
    {
        $this->applicationEmailList = $applicationEmailList;

        return $this;
    }

    public function isDeleted(): ?bool
    {
        return $this->isDeleted;
    }

    public function setDeleted(bool $isDeleted): static
    {
        $this->isDeleted = $isDeleted;

        return $this;
    }

    /**
     * @return Collection<int, DeviceAlarmSetupGeneral>
     */
    public function getDeviceAlarmSetupGenerals(): Collection
    {
        return $this->deviceAlarmSetupGenerals;
    }

    public function addDeviceAlarmSetupGeneral(DeviceAlarmSetupGeneral $deviceAlarmSetupGeneral): static
    {
        if (!$this->deviceAlarmSetupGenerals->contains($deviceAlarmSetupGeneral)) {
            $this->deviceAlarmSetupGenerals->add($deviceAlarmSetupGeneral);
            $deviceAlarmSetupGeneral->setDevice($this);
        }

        return $this;
    }

    public function removeDeviceAlarmSetupGeneral(DeviceAlarmSetupGeneral $deviceAlarmSetupGeneral): static
    {
        if ($this->deviceAlarmSetupGenerals->removeElement($deviceAlarmSetupGeneral)) {
            // set the owning side to null (unless already changed)
            if ($deviceAlarmSetupGeneral->getDevice() === $this) {
                $deviceAlarmSetupGeneral->setDevice(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, DeviceAlarmSetupEntry>
     */
    public function getDeviceAlarmSetupEntries(): Collection
    {
        return $this->deviceAlarmSetupEntries;
    }

    public function addDeviceAlarmSetupEntry(DeviceAlarmSetupEntry $deviceAlarmSetupEntry): static
    {
        if (!$this->deviceAlarmSetupEntries->contains($deviceAlarmSetupEntry)) {
            $this->deviceAlarmSetupEntries->add($deviceAlarmSetupEntry);
            $deviceAlarmSetupEntry->setDevice($this);
        }

        return $this;
    }

    public function removeDeviceAlarmSetupEntry(DeviceAlarmSetupEntry $deviceAlarmSetupEntry): static
    {
        if ($this->deviceAlarmSetupEntries->removeElement($deviceAlarmSetupEntry)) {
            // set the owning side to null (unless already changed)
            if ($deviceAlarmSetupEntry->getDevice() === $this) {
                $deviceAlarmSetupEntry->setDevice(null);
            }
        }

        return $this;
    }
}
