<?php

namespace Cauri\MediaLibrary\MediaRepository;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Cauri\MediaLibrary\Models\Media;
use Cauri\MediaLibrary\Collections\MediaCollection;

class MediaRepository
{
    protected string $mediaClass;

    public function __construct()
    {
        $this->mediaClass = config('cauri-media-library.media_model');
    }

    /**
     * Find media by ID
     */
    public function findById(int $id): ?Media
    {
        return $this->mediaClass::find($id);
    }

    /**
     * Find media by UUID
     */
    public function findByUuid(string $uuid): ?Media
    {
        return $this->mediaClass::where('uuid', $uuid)->first();
    }

    /**
     * Get all media for a model
     */
    public function getByModel(Model $model, string $collectionName = ''): MediaCollection
    {
        $query = $this->buildModelQuery($model);

        if ($collectionName !== '') {
            $query->where('collection_name', $collectionName);
        }

        return MediaCollection::make($query->ordered()->get());
    }

    /**
     * Get media by collection name
     */
    public function getByCollection(string $collectionName): MediaCollection
    {
        $media = $this->mediaClass::where('collection_name', $collectionName)
            ->ordered()
            ->get();

        return MediaCollection::make($media);
    }

    /**
     * Get images only
     */
    public function getImages(?Model $model = null): MediaCollection
    {
        $query = $this->mediaClass::where('mime_type', 'like', 'image/%');

        if ($model) {
            $query = $this->applyModelFilter($query, $model);
        }

        return MediaCollection::make($query->ordered()->get());
    }

    /**
     * Get videos only
     */
    public function getVideos(?Model $model = null): MediaCollection
    {
        $query = $this->mediaClass::where('mime_type', 'like', 'video/%');

        if ($model) {
            $query = $this->applyModelFilter($query, $model);
        }

        return MediaCollection::make($query->ordered()->get());
    }

    /**
     * Get audio files only
     */
    public function getAudio(?Model $model = null): MediaCollection
    {
        $query = $this->mediaClass::where('mime_type', 'like', 'audio/%');

        if ($model) {
            $query = $this->applyModelFilter($query, $model);
        }

        return MediaCollection::make($query->ordered()->get());
    }

    /**
     * Get documents only
     */
    public function getDocuments(?Model $model = null): MediaCollection
    {
        $query = $this->mediaClass::where('mime_type', 'like', 'application/%');

        if ($model) {
            $query = $this->applyModelFilter($query, $model);
        }

        return MediaCollection::make($query->ordered()->get());
    }

    /**
     * Search media
     */
    public function search(string $query, ?Model $model = null): MediaCollection
    {
        $searchQuery = $this->mediaClass::where(function ($q) use ($query) {
            $q->where('name', 'like', "%{$query}%")
              ->orWhere('file_name', 'like', "%{$query}%")
              ->orWhere('custom_properties', 'like', "%{$query}%");
        });

        if ($model) {
            $searchQuery = $this->applyModelFilter($searchQuery, $model);
        }

        return MediaCollection::make($searchQuery->ordered()->get());
    }

    /**
     * Get recent media
     */
    public function getRecent(int $limit = 10, ?Model $model = null): MediaCollection
    {
        $query = $this->mediaClass::orderBy('created_at', 'desc')->limit($limit);

        if ($model) {
            $query = $this->applyModelFilter($query, $model);
        }

        return MediaCollection::make($query->get());
    }

    /**
     * Get total size
     */
    public function getTotalSize(?Model $model = null): int
    {
        $query = $this->mediaClass::query();

        if ($model) {
            $query = $this->applyModelFilter($query, $model);
        }

        return $query->sum('size');
    }

    /**
     * Get media count by type
     */
    public function getCountByType(?Model $model = null): array
    {
        $query = $this->mediaClass::query();

        if ($model) {
            $query = $this->applyModelFilter($query, $model);
        }

        return [
            'total' => $query->count(),
            'images' => $query->clone()->where('mime_type', 'like', 'image/%')->count(),
            'videos' => $query->clone()->where('mime_type', 'like', 'video/%')->count(),
            'audio' => $query->clone()->where('mime_type', 'like', 'audio/%')->count(),
            'documents' => $query->clone()->where('mime_type', 'like', 'application/%')->count(),
        ];
    }

    /**
     * Delete old media (soft deleted)
     */
    public function deleteOldMedia(int $days = 30): int
    {
        return $this->mediaClass::onlyTrashed()
            ->where('deleted_at', '<', now()->subDays($days))
            ->forceDelete();
    }

    /**
     * Reorder collection
     */
    public function reorderCollection(Model $model, string $collectionName, array $mediaIds): bool
    {
        foreach ($mediaIds as $order => $mediaId) {
            $this->mediaClass::where('model_type', get_class($model))
                ->where('model_id', $model->getKey())
                ->where('collection_name', $collectionName)
                ->where('id', $mediaId)
                ->update(['order_column' => $order + 1]);
        }

        return true;
    }

    /**
     * Get collections for model
     */
    public function getCollectionsForModel(Model $model): Collection
    {
        return $this->mediaClass::where('model_type', get_class($model))
            ->where('model_id', $model->getKey())
            ->select('collection_name')
            ->selectRaw('COUNT(*) as media_count')
            ->selectRaw('SUM(size) as total_size')
            ->groupBy('collection_name')
            ->orderBy('collection_name')
            ->get();
    }

    /**
     * Bulk update media
     */
    public function bulkUpdate(array $mediaIds, array $data): int
    {
        return $this->mediaClass::whereIn('id', $mediaIds)->update($data);
    }

    /**
     * Bulk delete media
     */
    public function bulkDelete(array $mediaIds): int
    {
        return $this->mediaClass::whereIn('id', $mediaIds)->delete();
    }

    /**
     * Build query for model
     */
    protected function buildModelQuery(Model $model): Builder
    {
        return $this->mediaClass::where('model_type', get_class($model))
            ->where('model_id', $model->getKey());
    }

    /**
     * Apply model filter to query
     */
    protected function applyModelFilter(Builder $query, Model $model): Builder
    {
        return $query->where('model_type', get_class($model))
            ->where('model_id', $model->getKey());
    }
}