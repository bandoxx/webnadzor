<?php

namespace App\Service\ClientStorage;

class ScadaModel
{

    public const TEXT = 'text';
    public const DEVICE = 'device';

    private string $type;
    private string $text;
    private string $fontColor;
    private int $fontSize;
    private ?string $url;
    private int $positionX;
    private int $positionY;
    private bool $activeBackground;

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function setText(string $text): self
    {
        $this->text = $text;
        return $this;
    }

    public function getFontColor(): string
    {
        return $this->fontColor;
    }

    public function setFontColor(string $fontColor): self
    {
        $this->fontColor = $fontColor;
        return $this;
    }

    public function getFontSize(): int
    {
        return $this->fontSize;
    }

    public function setFontSize(int $fontSize): self
    {
        $this->fontSize = $fontSize;
        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): self
    {
        $this->url = $url;
        return $this;
    }

    public function getPositionX(): int
    {
        return $this->positionX;
    }

    public function setPositionX(int $positionX): self
    {
        $this->positionX = $positionX;
        return $this;
    }

    public function getPositionY(): int
    {
        return $this->positionY;
    }

    public function setPositionY(int $positionY): self
    {
        $this->positionY = $positionY;
        return $this;
    }

    public function isActiveBackground(): bool
    {
        return $this->activeBackground;
    }

    public function setActiveBackground(bool $activeBackground): self
    {
        $this->activeBackground = $activeBackground;
        return $this;
    }


}