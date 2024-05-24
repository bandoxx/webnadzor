<?php

namespace App\Entity;

use App\Repository\DeviceDataRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: DeviceDataRepository::class)]
#[ORM\Index(name: 'idx_search', fields: ['device', 'deviceDate'])]
class DeviceData
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups('device_read')]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'deviceData')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Device $device = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups('device_read')]
    private ?\DateTimeInterface $serverDate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups('device_read')]
    private ?\DateTimeInterface $deviceDate = null;

    #[ORM\Column]
    #[Groups('device_read')]
    private ?int $gsmSignal = null;

    #[ORM\Column]
    #[Groups('device_read')]
    private ?bool $supply = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 2, scale: 1)]
    #[Groups('device_read')]
    private ?string $vbat = null;

    #[ORM\Column]
    #[Groups('device_read')]
    private ?int $battery = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $d1;

    #[ORM\Column(type: Types::DECIMAL, precision: 4, scale: 2, nullable: true)]
    private ?string $t1 = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2, nullable: true)]
    private ?string $rh1 = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 4, scale: 2, nullable: true)]
    private ?string $mkt1 = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 4, scale: 2, nullable: true)]
    private ?string $tAvrg1 = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 4, scale: 2, nullable: true)]
    private ?string $tMin1 = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 4, scale: 2, nullable: true)]
    private ?string $tMax1 = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $note1 = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $d2;

    #[ORM\Column(type: Types::DECIMAL, precision: 4, scale: 2, nullable: true)]
    private ?string $t2 = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2, nullable: true)]
    private ?string $rh2 = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 4, scale: 2, nullable: true)]
    private ?string $mkt2 = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 4, scale: 2, nullable: true)]
    private ?string $tAvrg2 = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 4, scale: 2, nullable: true)]
    private ?string $tMin2 = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 4, scale: 2, nullable: true)]
    private ?string $tMax2 = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $note2 = null;


    public function getId(): ?int
    {
        return $this->id;
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

    public function getServerDate(): ?\DateTimeInterface
    {
        return $this->serverDate;
    }

    public function setServerDate(\DateTimeInterface $serverDate): static
    {
        $this->serverDate = $serverDate;

        return $this;
    }

    public function getDeviceDate(): ?\DateTimeInterface
    {
        return $this->deviceDate;
    }

    public function setDeviceDate(\DateTimeInterface $deviceDate): static
    {
        $this->deviceDate = $deviceDate;

        return $this;
    }

    public function getGsmSignal(): ?int
    {
        return $this->gsmSignal;
    }

    public function setGsmSignal(int $gsmSignal): static
    {
        $this->gsmSignal = $gsmSignal;

        return $this;
    }

    public function isSupply(): ?bool
    {
        return $this->supply;
    }

    public function setSupply(bool $supply): static
    {
        $this->supply = $supply;

        return $this;
    }

    public function getVbat(): ?string
    {
        return $this->vbat;
    }

    public function setVbat(string $vbat): static
    {
        $this->vbat = $vbat;

        return $this;
    }

    public function getBattery(): ?int
    {
        return $this->battery;
    }

    public function setBattery(int $battery): static
    {
        $this->battery = $battery;

        return $this;
    }

    public function isTemperatureOutOfRange($entry): bool
    {
        return $this->isTemperatureHigh($entry) || $this->isTemperatureLow($entry);
    }

    public function isTemperatureLow($entry): bool
    {
        $getter = "getT$entry";
        $temperature = $this->$getter();

        if (!is_numeric($temperature)) {
            return false;
        }

        $deviceEntrySettings = $this->getDevice()->getEntryData($entry);

        $minimum = $deviceEntrySettings['t_min'];

        return $minimum && $minimum > $temperature;
    }

    public function isTemperatureHigh($entry): bool
    {
        $getter = "getT$entry";
        $temperature = $this->$getter();

        if (!is_numeric($temperature)) {
            return false;
        }

        $deviceEntrySettings = $this->getDevice()->getEntryData($entry);

        $maximum = $deviceEntrySettings['t_max'];

        return $maximum && $maximum < $temperature;
    }

    public function isHumidityOutOfRange($entry): bool
    {
        return $this->isHumidityLow($entry) || $this->isHumidityHigh($entry);
    }

    public function isHumidityLow($entry): bool
    {
        $getter = "getRh$entry";
        $humidity = $this->$getter();

        if (!is_numeric($humidity)) {
            return false;
        }

        $deviceEntrySettings = $this->getDevice()->getEntryData($entry);

        $minimum = $deviceEntrySettings['rh_min'] ?? null;

        return $minimum && $minimum > $humidity;
    }

    public function isHumidityHigh($entry): bool
    {
        $getter = "getRh$entry";
        $humidity = $this->$getter();

        if (!is_numeric($humidity)) {
            return false;
        }

        $deviceEntrySettings = $this->getDevice()->getEntryData($entry);

        $maximum = $deviceEntrySettings['rh_max'] ?? null;

        return $maximum && $maximum < $humidity;
    }

    public function isD($entry): bool
    {
        $getter = "isD$entry";

        return $this->$getter();
    }

    public function getT($entry): ?string
    {
        $getter = "getT$entry";

        return $this->$getter();
    }

    public function getRh($entry): ?string
    {
        $getter = "getRh$entry";

        return $this->$getter();
    }

    public function getTMin($entry): ?string
    {
        $getter = "getTMin$entry";

        return $this->$getter();
    }

    public function getTMax($entry): ?string
    {
        $getter = "getTMax$entry";

        return $this->$getter();
    }

    public function getTAvrg($entry): ?string
    {
        $getter = "getTAvrg$entry";

        return $this->$getter();
    }

    public function getMkt($entry): ?string
    {
        $getter = "getMkt$entry";

        return $this->$getter();
    }

    public function getNote($entry): ?string
    {
        $getter = "getNote$entry";

        return $this->$getter();
    }

    public function setNote($entry, $note): static
    {
        $setter = "setNote$entry";

        return $this->$setter($note);
    }

    public function isD1(): bool
    {
        return $this->d1;
    }

    public function setD1(bool $d1): static
    {
        $this->d1 = $d1;

        return $this;
    }

    public function getT1(): ?string
    {
        return $this->t1;
    }

    public function setT1(?string $t1): static
    {
        $this->t1 = $t1;

        return $this;
    }

    public function getRh1(): ?string
    {
        return $this->rh1;
    }

    public function setRh1(?string $rh1): static
    {
        $this->rh1 = $rh1;

        return $this;
    }

    public function getMkt1(): ?string
    {
        return $this->mkt1;
    }

    public function setMkt1(?string $mkt1): static
    {
        $this->mkt1 = $mkt1;

        return $this;
    }

    public function getTAvrg1(): ?string
    {
        return $this->tAvrg1;
    }

    public function setTAvrg1(?string $tAvrg1): static
    {
        $this->tAvrg1 = $tAvrg1;

        return $this;
    }

    public function getTMin1(): ?string
    {
        return $this->tMin1;
    }

    public function setTMin1(?string $tMin1): static
    {
        $this->tMin1 = $tMin1;

        return $this;
    }

    public function getTMax1(): ?string
    {
        return $this->tMax1;
    }

    public function setTMax1(?string $tMax1): static
    {
        $this->tMax1 = $tMax1;

        return $this;
    }

    public function getNote1(): ?string
    {
        return $this->note1;
    }

    public function setNote1(?string $note1): static
    {
        $this->note1 = $note1;

        return $this;
    }

    public function isD2(): bool
    {
        return $this->d2;
    }

    public function setD2(bool $d2): static
    {
        $this->d2 = $d2;

        return $this;
    }

    public function getT2(): ?string
    {
        return $this->t2;
    }

    public function setT2(?string $t2): static
    {
        $this->t2 = $t2;

        return $this;
    }

    public function getRh2(): ?string
    {
        return $this->rh2;
    }

    public function setRh2(?string $rh2): static
    {
        $this->rh2 = $rh2;

        return $this;
    }

    public function getMkt2(): ?string
    {
        return $this->mkt2;
    }

    public function setMkt2(?string $mkt2): static
    {
        $this->mkt2 = $mkt2;

        return $this;
    }

    public function getTAvrg2(): ?string
    {
        return $this->tAvrg2;
    }

    public function setTAvrg2(?string $tAvrg2): static
    {
        $this->tAvrg2 = $tAvrg2;

        return $this;
    }

    public function getTMin2(): ?string
    {
        return $this->tMin2;
    }

    public function setTMin2(?string $tMin2): static
    {
        $this->tMin2 = $tMin2;

        return $this;
    }

    public function getNote2(): ?string
    {
        return $this->note2;
    }

    public function setNote2(?string $note2): static
    {
        $this->note2 = $note2;

        return $this;
    }

    public function getTMax2(): ?string
    {
        return $this->tMax2;
    }

    public function setTMax2(?string $tMax2): static
    {
        $this->tMax2 = $tMax2;

        return $this;
    }
}
