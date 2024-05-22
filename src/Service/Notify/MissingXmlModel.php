<?php

namespace App\Service\Notify;

use App\Entity\Device;

class MissingXmlModel
{

    private Device $device;
    private int $expectedNumberOfLogs;
    private int $numberOfLogs;

    public function getDevice(): Device
    {
        return $this->device;
    }

    public function setDevice(Device $device): static
    {
        $this->device = $device;
        return $this;
    }

    public function getExpectedNumberOfLogs(): int
    {
        return $this->expectedNumberOfLogs;
    }

    public function setExpectedNumberOfLogs(int $expectedNumberOfLogs): static
    {
        $this->expectedNumberOfLogs = $expectedNumberOfLogs;
        return $this;
    }

    public function getNumberOfLogs(): int
    {
        return $this->numberOfLogs;
    }

    public function setNumberOfLogs(int $numberOfLogs): static
    {
        $this->numberOfLogs = $numberOfLogs;
        return $this;
    }
}