<?php

namespace Cauri\MediaLibrary\Collections;

use Cauri\MediaLibrary\Models\Media;

class MediaCollectionSerializer
{
    /**
     * Serialize media collection to array
     */
    public function toArray(MediaCollection $collection): array
    {
        return $collection->map(function (Media $media) {
            return $this->serializeMedia($media);
        })->toArray();
    }

    /**
     * Serialize media collection to JSON
     */
    public function toJson(MediaCollection $collection, int $options = 0): string
    {
        return json_encode($this->toArray($collection), $options);
    }

    /**
     * Serialize single media to array
     */
    public function serializeMedia(Media $media): array
    {
        return [
            'id' => $media->id,
            'uuid' => $media->uuid,
            'name' => $media->name,
            'file_name' => $media->file_name,
            'collection_name' => $media->collection_name,
            'mime_type' => $media->mime_type,
            'size' => $media->size,
            'human_readable_size' => $media->human_readable_size,
            'type' => $media->type,
            'extension' => $media->extension,
            'urls' => [
                'original' => $media->getUrl(),
                'thumb' => $media->getUrl('thumb'),
                'medium' => $media->getUrl('medium'),
                'large' => $media->getUrl('large'),
            ],
            'custom_properties' => $media->custom_properties,
            'order_column' => $media->order_column,
            'created_at' => $media->created_at,
            'updated_at' => $media->updated_at,
        ];
    }

    /**
     * Serialize for API response
     */
    public function toApiResponse(MediaCollection $collection): array
    {
        return [
            'data' => $this->toArray($collection),
            'meta' => [
                'total' => $collection->count(),
                'total_size' => $collection->getTotalSize(),
                'total_size_human' => $collection->getHumanTotalSize(),
                'types' => $this->getTypeCounts($collection),
            ]
        ];
    }

    /**
     * Serialize for Vue.js components
     */
    public function toVueFormat(MediaCollection $collection): array
    {
        return $collection->map(function (Media $media) {
            return [
                'id' => $media->id,
                'name' => $media->name,
                'url' => $media->getUrl(),
                'thumb_url' => $media->getUrl('thumb'),
                'type' => $media->type,
                'size' => $media->human_readable_size,
                'mime_type' => $media->mime_type,
                'collection' => $media->collection_name,
                'created_at' => $media->created_at->format('d/m/Y H:i'),
                'editable' => true,
                'selected' => false,
            ];
        })->toArray();
    }

    /**
     * Serialize for gallery display
     */
    public function toGalleryFormat(MediaCollection $collection): array
    {
        return $collection->getImages()->map(function (Media $media) {
            return [
                'id' => $media->id,
                'src' => $media->getUrl(),
                'thumb' => $media->getUrl('thumb'),
                'alt' => $media->name,
                'title' => $media->name,
                'description' => $media->getCustomProperty('description', ''),
                'width' => $media->getCustomProperty('width'),
                'height' => $media->getCustomProperty('height'),
            ];
        })->toArray();
    }

    /**
     * Get type counts
     */
    protected function getTypeCounts(MediaCollection $collection): array
    {
        $counts = [
            'images' => 0,
            'videos' => 0,
            'audio' => 0,
            'documents' => 0,
            'others' => 0,
        ];

        foreach ($collection as $media) {
            switch ($media->type) {
                case 'image':
                    $counts['images']++;
                    break;
                case 'video':
                    $counts['videos']++;
                    break;
                case 'audio':
                    $counts['audio']++;
                    break;
                case 'document':
                case 'pdf':
                    $counts['documents']++;
                    break;
                default:
                    $counts['others']++;
            }
        }

        return $counts;
    }
}