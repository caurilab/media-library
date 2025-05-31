<?php

namespace Cauri\MediaLibrary\Collections;

use Illuminate\Support\Collection;
use Cauri\MediaLibrary\Models\Media;

class MediaCollection extends Collection
{
    public function getByName(string $name): ?Media
    {
        return $this->first(fn (Media $media) => $media->name === $name);
    }

    public function getByFileName(string $fileName): ?Media
    {
        return $this->first(fn (Media $media) => $media->file_name === $fileName);
    }

    public function getByUuid(string $uuid): ?Media
    {
        return $this->first(fn (Media $media) => $media->uuid === $uuid);
    }

    public function getImages(): self
    {
        return $this->filter(fn (Media $media) => $media->isImage());
    }

    public function getVideos(): self
    {
        return $this->filter(fn (Media $media) => $media->isVideo());
    }

    public function getAudio(): self
    {
        return $this->filter(fn (Media $media) => $media->isAudio());
    }

    public function getDocuments(): self
    {
        return $this->filter(fn (Media $media) => 
            !$media->isImage() && !$media->isVideo() && !$media->isAudio()
        );
    }

    public function getTotalSize(): int
    {
        return $this->sum('size');
    }

    public function getHumanTotalSize(): string
    {
        return $this->first()?->humanFileSize($this->getTotalSize()) ?? '0 B';
    }

    // public function toArray(): array
    // {
    //     return $this->map(function (Media $media) {
    //         return [
    //             'id' => $media->id,
    //             'name' => $media->name,
    //             'file_name' => $media->file_name,
    //             'collection_name' => $media->collection_name,
    //             'mime_type' => $media->mime_type,
    //             'size' => $media->size,
    //             'human_readable_size' => $media->human_readable_size,
    //             'url' => $media->getUrl(),
    //             'thumb_url' => $media->getUrl('thumb'),
    //             'type' => $media->type,
    //             'extension' => $media->extension,
    //             'custom_properties' => $media->custom_properties,
    //             'created_at' => $media->created_at,
    //             'updated_at' => $media->updated_at,
    //         ];
    //     })->toArray();
    // }

    // public function toJson($options = 0): string
    // {
    //     return json_encode($this->toArray(), $options);
    // }

        /**
     * Convert to array using serializer
     */
    public function toArray(): array
    {
        $serializer = new MediaCollectionSerializer();
        return $serializer->toArray($this);
    }

    /**
     * Convert to JSON using serializer
     */
    public function toJson($options = 0): string
    {
        $serializer = new MediaCollectionSerializer();
        return $serializer->toJson($this, $options);
    }

    /**
     * Format for API response
     */
    public function toApiResponse(): array
    {
        $serializer = new MediaCollectionSerializer();
        return $serializer->toApiResponse($this);
    }

    /**
     * Format for Vue.js components
     */
    public function toVueFormat(): array
    {
        $serializer = new MediaCollectionSerializer();
        return $serializer->toVueFormat($this);
    }

    /**
     * Format for gallery display
     */
    public function toGalleryFormat(): array
    {
        $serializer = new MediaCollectionSerializer();
        return $serializer->toGalleryFormat($this);
    }
}