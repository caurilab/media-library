<?php

namespace Cauri\MediaLibrary\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Cauri\MediaLibrary\Http\Requests\MediaRequest;
use Cauri\MediaLibrary\Http\Requests\MediaUploadRequest;
use Cauri\MediaLibrary\Models\Media;
use Cauri\MediaLibrary\MediaRepository\MediaRepository;
use Cauri\MediaLibrary\Jobs\GenerateConversions;
use Cauri\MediaLibrary\Exceptions\FileCannotBeAdded;

class MediaApiController extends Controller
{
    protected MediaRepository $mediaRepository;

    public function __construct(MediaRepository $mediaRepository)
    {
        $this->mediaRepository = $mediaRepository;
    }

    public function upload(MediaUploadRequest $request): JsonResponse
    {
        try {
            $uploadedMedia = collect();

            // Get model instance if provided
            $model = null;
            if ($request->filled(['model_type', 'model_id'])) {
                $modelClass = $request->input('model_type');
                $model = $modelClass::findOrFail($request->input('model_id'));
            }

            // Process each uploaded file
            foreach ($request->file('files', []) as $file) {
                if (!$model) {
                    // If no model provided, create a standalone media record
                    $media = app(\Cauri\MediaLibrary\FileAdder\FileAdder::class)
                        ->setFile($file)
                        ->withCustomProperties($request->input('custom_properties', []))
                        ->toMediaCollection($request->input('collection', 'default'));
                } else {
                    $media = $model
                        ->addMedia($file)
                        ->withCustomProperties($request->input('custom_properties', []))
                        ->toMediaCollection($request->input('collection', 'default'));
                }

                $uploadedMedia->push($this->formatMediaResponse($media));
            }

            return response()->json([
                'success' => true,
                'message' => 'Files uploaded successfully',
                'data' => [
                    'media' => $uploadedMedia,
                    'count' => $uploadedMedia->count(),
                ],
            ], 201);

        } catch (FileCannotBeAdded $e) {
            return response()->json([
                'success' => false,
                'message' => 'Upload failed',
                'error' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    public function uploadFromUrl(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'url' => 'required|url',
            'model_type' => 'sometimes|string',
            'model_id' => 'sometimes|integer',
            'collection' => 'sometimes|string|max:255',
            'name' => 'sometimes|string|max:255',
            'custom_properties' => 'sometimes|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Get model instance if provided
            $model = null;
            if ($request->filled(['model_type', 'model_id'])) {
                $modelClass = $request->input('model_type');
                $model = $modelClass::findOrFail($request->input('model_id'));
            }

            $fileAdder = $model 
                ? $model->addMediaFromUrl($request->input('url'))
                : app(\Cauri\MediaLibrary\FileAdder\FileAdder::class)
                    ->setFile($request->input('url'));

            if ($request->filled('name')) {
                $fileAdder->usingName($request->input('name'));
            }

            $media = $fileAdder
                ->withCustomProperties($request->input('custom_properties', []))
                ->toMediaCollection($request->input('collection', 'default'));

            return response()->json([
                'success' => true,
                'message' => 'File uploaded from URL successfully',
                'data' => $this->formatMediaResponse($media),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload from URL',
                'error' => config('app.debug') ? $e->getMessage() : 'Upload failed',
            ], 422);
        }
    }

    public function show(Media $media): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->formatMediaResponse($media, true),
        ]);
    }

    public function update(MediaRequest $request, Media $media): JsonResponse
    {
        try {
            $media->update($request->validated());

            // Update custom properties if provided
            if ($request->has('custom_properties')) {
                $customProperties = array_merge(
                    $media->custom_properties ?? [],
                    $request->input('custom_properties', [])
                );
                $media->custom_properties = $customProperties;
                $media->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'Media updated successfully',
                'data' => $this->formatMediaResponse($media),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update media',
                'error' => config('app.debug') ? $e->getMessage() : 'Update failed',
            ], 500);
        }
    }

    public function destroy(Media $media): JsonResponse
    {
        try {
            $media->delete();

            return response()->json([
                'success' => true,
                'message' => 'Media deleted successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete media',
                'error' => config('app.debug') ? $e->getMessage() : 'Delete failed',
            ], 500);
        }
    }

    public function getCollection(Request $request, string $collection): JsonResponse
    {
        $validator = Validator::make(array_merge($request->all(), ['collection' => $collection]), [
            'collection' => 'required|string|max:255',
            'model_type' => 'sometimes|string',
            'model_id' => 'sometimes|integer',
            'per_page' => 'sometimes|integer|min:1|max:100',
            'page' => 'sometimes|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            if ($request->filled(['model_type', 'model_id'])) {
                $modelClass = $request->input('model_type');
                $model = $modelClass::findOrFail($request->input('model_id'));
                $mediaCollection = $this->mediaRepository->getByModel($model, $collection);
            } else {
                $mediaCollection = $this->mediaRepository->getByCollection($collection);
            }

            // Pagination
            $perPage = $request->input('per_page', 20);
            $page = $request->input('page', 1);
            $offset = ($page - 1) * $perPage;
            
            $paginatedMedia = $mediaCollection->slice($offset, $perPage);
            $total = $mediaCollection->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'media' => $paginatedMedia->map(fn($media) => $this->formatMediaResponse($media)),
                    'pagination' => [
                        'current_page' => $page,
                        'per_page' => $perPage,
                        'total' => $total,
                        'last_page' => ceil($total / $perPage),
                        'has_more' => $offset + $perPage < $total,
                    ],
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve collection',
                'error' => config('app.debug') ? $e->getMessage() : 'Query failed',
            ], 500);
        }
    }

    public function reorder(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'model_type' => 'required|string',
            'model_id' => 'required|integer',
            'collection' => 'required|string|max:255',
            'media_ids' => 'required|array',
            'media_ids.*' => 'integer|exists:media,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $modelClass = $request->input('model_type');
            $model = $modelClass::findOrFail($request->input('model_id'));
            
            $success = $this->mediaRepository->reorderCollection(
                $model,
                $request->input('collection'),
                $request->input('media_ids')
            );

            return response()->json([
                'success' => $success,
                'message' => $success ? 'Media reordered successfully' : 'Failed to reorder media',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reorder media',
                'error' => config('app.debug') ? $e->getMessage() : 'Reorder failed',
            ], 500);
        }
    }

    public function getConversions(Media $media): JsonResponse
    {
        $conversions = [];
        $generatedConversions = $media->generated_conversions ?? [];

        foreach ($generatedConversions as $conversionName => $isGenerated) {
            $conversions[] = [
                'name' => $conversionName,
                'generated' => $isGenerated,
                'url' => $isGenerated ? $media->getUrl($conversionName) : null,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'media_id' => $media->id,
                'conversions' => $conversions,
            ],
        ]);
    }

    public function regenerateConversions(Media $media): JsonResponse
    {
        try {
            GenerateConversions::dispatch($media);

            return response()->json([
                'success' => true,
                'message' => 'Conversion regeneration queued successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to queue conversion regeneration',
                'error' => config('app.debug') ? $e->getMessage() : 'Queue failed',
            ], 500);
        }
    }

    public function bulkDelete(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'media_ids' => 'required|array',
            'media_ids.*' => 'integer|exists:media,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $mediaIds = $request->input('media_ids');
            $deleted = Media::whereIn('id', $mediaIds)->delete();

            return response()->json([
                'success' => true,
                'message' => "Successfully deleted {$deleted} media items",
                'data' => [
                    'deleted_count' => $deleted,
                    'requested_count' => count($mediaIds),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Bulk delete failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Delete failed',
            ], 500);
        }
    }

    public function bulkUpdate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'media_ids' => 'required|array',
            'media_ids.*' => 'integer|exists:media,id',
            'updates' => 'required|array',
            'updates.collection_name' => 'sometimes|string|max:255',
            'updates.custom_properties' => 'sometimes|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $mediaIds = $request->input('media_ids');
            $updates = $request->input('updates');
            
            $updated = Media::whereIn('id', $mediaIds)->update($updates);

            return response()->json([
                'success' => true,
                'message' => "Successfully updated {$updated} media items",
                'data' => [
                    'updated_count' => $updated,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Bulk update failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Update failed',
            ], 500);
        }
    }

    public function search(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'q' => 'required|string|min:2|max:255',
            'model_type' => 'sometimes|string',
            'model_id' => 'sometimes|integer',
            'collection' => 'sometimes|string|max:255',
            'type' => 'sometimes|in:image,video,audio,document,archive',
            'per_page' => 'sometimes|integer|min:1|max:100',
            'page' => 'sometimes|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $query = $request->input('q');
            $model = null;

            if ($request->filled(['model_type', 'model_id'])) {
                $modelClass = $request->input('model_type');
                $model = $modelClass::findOrFail($request->input('model_id'));
            }

            $results = $this->mediaRepository->search($query, $model);

            // Apply additional filters
            if ($request->filled('collection')) {
                $results = $results->filter(fn($media) => $media->collection_name === $request->input('collection'));
            }

            if ($request->filled('type')) {
                $type = $request->input('type');
                $results = $results->filter(function($media) use ($type) {
                    return match($type) {
                        'image' => $media->isImage(),
                        'video' => $media->isVideo(),
                        'audio' => $media->isAudio(),
                        'document' => $media->isPdf(),
                        'archive' => $media->isArchive(),
                        default => true,
                    };
                });
            }

            // Pagination
            $perPage = $request->input('per_page', 20);
            $page = $request->input('page', 1);
            $offset = ($page - 1) * $perPage;
            
            $paginatedResults = $results->slice($offset, $perPage);
            $total = $results->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'query' => $query,
                    'results' => $paginatedResults->map(fn($media) => $this->formatMediaResponse($media)),
                    'pagination' => [
                        'current_page' => $page,
                        'per_page' => $perPage,
                        'total' => $total,
                        'last_page' => ceil($total / $perPage),
                    ],
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Search failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Search error',
            ], 500);
        }
    }

    public function getStats(Request $request): JsonResponse
    {
        try {
            $model = null;
            if ($request->filled(['model_type', 'model_id'])) {
                $modelClass = $request->input('model_type');
                $model = $modelClass::findOrFail($request->input('model_id'));
            }

            $totalSize = $this->mediaRepository->getTotalSize($model);
            $mediaQuery = $model 
                ? Media::where('model_type', get_class($model))->where('model_id', $model->getKey())
                : Media::query();

            $stats = [
                'total_files' => $mediaQuery->count(),
                'total_size' => $totalSize,
                'total_size_human' => $this->humanFileSize($totalSize),
                'by_type' => [
                    'images' => $mediaQuery->clone()->where('mime_type', 'like', 'image/%')->count(),
                    'videos' => $mediaQuery->clone()->where('mime_type', 'like', 'video/%')->count(),
                    'audio' => $mediaQuery->clone()->where('mime_type', 'like', 'audio/%')->count(),
                    'documents' => $mediaQuery->clone()->where('mime_type', 'like', 'application/%')->count(),
                ],
                'by_collection' => $mediaQuery->clone()
                    ->selectRaw('collection_name, COUNT(*) as count')
                    ->groupBy('collection_name')
                    ->pluck('count', 'collection_name')
                    ->toArray(),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve stats',
                'error' => config('app.debug') ? $e->getMessage() : 'Stats error',
            ], 500);
        }
    }

    protected function formatMediaResponse(Media $media, bool $detailed = false): array
    {
        $response = [
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
            'url' => $media->getUrl(),
            'urls' => [
                'original' => $media->getUrl(),
                'thumb' => $media->getUrl('thumb'),
                'medium' => $media->getUrl('medium'),
            ],
            'created_at' => $media->created_at,
            'updated_at' => $media->updated_at,
        ];

        if ($detailed) {
            $response = array_merge($response, [
                'model_type' => $media->model_type,
                'model_id' => $media->model_id,
                'disk' => $media->disk,
                'custom_properties' => $media->custom_properties,
                'generated_conversions' => $media->generated_conversions,
                'order_column' => $media->order_column,
                'path' => $media->getPath(),
            ]);
        }

        return $response;
    }

    protected function humanFileSize(int $bytes, int $decimals = 2): string
    {
        $size = ['B', 'kB', 'MB', 'GB', 'TB', 'PB'];
        $factor = floor((strlen($bytes) - 1) / 3);

        return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . ' ' . @$size[$factor];
    }
}