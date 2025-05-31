<?php

namespace Cauri\MediaLibrary\Support;

class ConversionDefinition
{
    protected string $name;
    protected int $width = 0;
    protected int $height = 0;
    protected int $quality = 85;
    protected string $format = '';
    protected string $fit = 'contain'; // contain, cover, crop, fill
    protected bool $queued = true;
    protected bool $nonOptimized = false;
    protected array $customProperties = [];
    protected array $performOnCollections = [];

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function width(int $width): self
    {
        $this->width = $width;
        return $this;
    }

    public function height(int $height): self
    {
        $this->height = $height;
        return $this;
    }

    public function quality(int $quality): self
    {
        $this->quality = max(1, min(100, $quality));
        return $this;
    }

    public function format(string $format): self
    {
        $this->format = strtolower($format);
        return $this;
    }

    public function fit(string $fit): self
    {
        $allowedFits = ['contain', 'cover', 'crop', 'fill'];
        if (in_array($fit, $allowedFits)) {
            $this->fit = $fit;
        }
        return $this;
    }

    public function queued(bool $queued = true): self
    {
        $this->queued = $queued;
        return $this;
    }

    public function nonQueued(): self
    {
        return $this->queued(false);
    }

    public function nonOptimized(bool $nonOptimized = true): self
    {
        $this->nonOptimized = $nonOptimized;
        return $this;
    }

    public function performOnCollections(array $collections): self
    {
        $this->performOnCollections = $collections;
        return $this;
    }

    public function addCustomProperty(string $key, $value): self
    {
        $this->customProperties[$key] = $value;
        return $this;
    }

    // Getters
    public function getName(): string
    {
        return $this->name;
    }

    public function getWidth(): int
    {
        return $this->width;
    }

    public function getHeight(): int
    {
        return $this->height;
    }

    public function getQuality(): int
    {
        return $this->quality;
    }

    public function getFormat(): string
    {
        return $this->format;
    }

    public function getFit(): string
    {
        return $this->fit;
    }

    public function isQueued(): bool
    {
        return $this->queued;
    }

    public function isOptimized(): bool
    {
        return !$this->nonOptimized;
    }

    public function getPerformOnCollections(): array
    {
        return $this->performOnCollections;
    }

    public function getCustomProperties(): array
    {
        return $this->customProperties;
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'width' => $this->width,
            'height' => $this->height,
            'quality' => $this->quality,
            'format' => $this->format,
            'fit' => $this->fit,
            'queued' => $this->queued,
            'optimized' => $this->isOptimized(),
            'perform_on_collections' => $this->performOnCollections,
            'custom_properties' => $this->customProperties,
        ];
    }
}