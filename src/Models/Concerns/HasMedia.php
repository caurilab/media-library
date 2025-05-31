<?php

namespace Cauri\MediaLibrary\Models\Concerns;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Http\UploadedFile;
use Cauri\MediaLibrary\FileAdder\FileAdder;
use Cauri\MediaLibrary\Models\Media;
use Cauri\MediaLibrary\Collections\MediaCollection;

trait HasMedia
{
    abstract public function registerMediaCollections(): void;
    abstract public function registerMediaConversions(?Media $media = null): void;

    public function media(): MorphMany
    {
        return $this->morphMany(config('cauri-media-library.media_model'), 'model')
            ->orderBy('order_column')
            ->orderBy('id');
    }

    public function addMedia($file): FileAdder
    {
        return app(FileAdder::class)
            ->setSubject($this)
            ->setFile($file);
    }

    public function addMediaFromUrl(string $url): FileAdder
    {
        return $this->addMedia($url);
    }

    public function addMediaFromString(string $string): FileAdder
    {
        return $this->addMedia($string);
    }

    public function getMedia(string $collectionName = 'default'): MediaCollection
    {
        return MediaCollection::make(
            $this->media
                ->filter(fn (Media $media) => $media->collection_name === $collectionName)
                ->values()
        );
    }

    public function getFirstMedia(string $collectionName = 'default'): ?Media
    {
        return $this->getMedia($collectionName)->first();
    }

    public function getFirstMediaUrl(string $collectionName = 'default', string $conversion = ''): string
    {
        $media = $this->getFirstMedia($collectionName);

        return $media ? $media->getUrl($conversion) : '';
    }

    public function getMediaUrl(int $mediaId, string $conversion = ''): string
    {
        $media = $this->media->find($mediaId);

        return $media ? $media->getUrl($conversion) : '';
    }

    public function clearMediaCollection(string $collectionName = 'default'): self
    {
        $this->getMedia($collectionName)->each->delete();

        return $this;
    }

    public function deleteMedia($mediaItems): self
    {
        if (!is_iterable($mediaItems)) {
            $mediaItems = [$mediaItems];
        }

        collect($mediaItems)->each->delete();

        return $this;
    }

    public function hasMedia(string $collectionName = 'default'): bool
    {
        return $this->getMedia($collectionName)->isNotEmpty();
    }

    public function getMediaCount(string $collectionName = 'default'): int
    {
        return $this->getMedia($collectionName)->count();
    }

    // Méthodes de configuration - à override dans les modèles
    // public function registerMediaCollections(): void
    // {
    //     // Override dans tes modèles
    // }

    // public function registerMediaConversions(?Media $media = null): void
    // {
    //     // Override dans tes modèles
    // }
}