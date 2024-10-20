<?php

namespace App\Entity;

use App\Repository\DeviceAlarmSetupGeneralRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DeviceAlarmSetupGeneralRepository::class)]
class DeviceAlarmSetupGeneral
{

    use DeviceAlarmSetupTrait;

    #[ORM\Column]
    private ?bool $isDevicePowerSupplyOffActive = null;

    public function isDevicePowerSupplyOffActive(): ?bool
    {
        return $this->isDevicePowerSupplyOffActive;
    }

    public function setDevicePowerSupplyOffActive(bool $isDevicePowerSupplyOffActive): static
    {
        $this->isDevicePowerSupplyOffActive = $isDevicePowerSupplyOffActive;

        return $this;
    }
}
