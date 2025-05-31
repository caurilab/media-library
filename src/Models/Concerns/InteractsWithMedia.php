<?php

namespace Cauri\MediaLibrary\Models\Concerns;

use Cauri\MediaLibrary\Models\Media;
use Cauri\MediaLibrary\Support\MediaCollectionDefinition;
use Cauri\MediaLibrary\Support\ConversionDefinition;

trait InteractsWithMedia
{
    use HasMedia;

    protected array $mediaCollections = [];
    protected array $mediaConversions = [];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('default');
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(300)
            ->height(300)
            ->quality(80)
            ->format('webp')
            ->nonQueued();
    }

    protected function addMediaCollection(string $name): MediaCollectionDefinition
    {
        $collection = new MediaCollectionDefinition($name);
        $this->mediaCollections[$name] = $collection;
        return $collection;
    }

    protected function addMediaConversion(string $name): ConversionDefinition
    {
        $conversion = new ConversionDefinition($name);
        $this->mediaConversions[$name] = $conversion;
        return $conversion;
    }

    public function getMediaCollections(): array
    {
        return $this->mediaCollections;
    }

    public function getMediaConversions(): array
    {
        return $this->mediaConversions;
    }

    public function getMediaCollection(string $name): ?MediaCollectionDefinition
    {
        return $this->mediaCollections[$name] ?? null;
    }

    public function getMediaConversion(string $name): ?ConversionDefinition
    {
        return $this->mediaConversions[$name] ?? null;
    }
}