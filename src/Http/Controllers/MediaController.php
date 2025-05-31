<?php

namespace Cauri\MediaLibrary\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Cauri\MediaLibrary\Models\Media;
use Cauri\MediaLibrary\MediaRepository\MediaRepository;

class MediaController extends Controller
{
    protected MediaRepository $mediaRepository;

    public function __construct(MediaRepository $mediaRepository)
    {
        $this->mediaRepository = $mediaRepository;
    }

    /**
     * Display a listing of media files
     */
    public function index(Request $request): View
    {
        $query = Media::with('model');

        // Apply filters
        if ($request->filled('collection')) {
            $query->where('collection_name', $request->input('collection'));
        }

        if ($request->filled('type')) {
            $type = $request->input('type');
            $query->when($type === 'image', fn($q) => $q->where('mime_type', 'like', 'image/%'))
                  ->when($type === 'video', fn($q) => $q->where('mime_type', 'like', 'video/%'))
                  ->when($type === 'audio', fn($q) => $q->where('mime_type', 'like', 'audio/%'))
                  ->when($type === 'document', fn($q) => $q->where('mime_type', 'like', 'application/%'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('file_name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('model_type')) {
            $query->where('model_type', $request->input('model_type'));
        }

        if ($request->filled('model_id')) {
            $query->where('model_id', $request->input('model_id'));
        }

        $media = $query->orderBy('created_at', 'desc')->paginate(24);

        // Get collections for filter dropdown
        $collections = Media::distinct()->pluck('collection_name')->sort();

        // Calculate total size
        $totalSize = Media::sum('size');

        // Get media type counts
        $typeCounts = [
            'total' => Media::count(),
            'images' => Media::where('mime_type', 'like', 'image/%')->count(),
            'videos' => Media::where('mime_type', 'like', 'video/%')->count(),
            'audio' => Media::where('mime_type', 'like', 'audio/%')->count(),
            'documents' => Media::where('mime_type', 'like', 'application/%')->count(),
        ];

        return view('cauri-media::index', compact(
            'media', 
            'collections', 
            'totalSize', 
            'typeCounts'
        ));
    }

    /**
     * Display media gallery (images only)
     */
    public function gallery(Request $request): View
    {
        $query = Media::images();

        if ($request->filled('collection')) {
            $query->where('collection_name', $request->input('collection'));
        }

        if ($request->filled('model_type')) {
            $query->where('model_type', $request->input('model_type'));
        }

        if ($request->filled('model_id')) {
            $query->where('model_id', $request->input('model_id'));
        }

        $media = $query->orderBy('created_at', 'desc')->paginate(32);
        
        $collections = Media::images()->distinct()->pluck('collection_name')->sort();

        return view('cauri-media::gallery', compact('media', 'collections'));
    }

    /**
     * Show media preview/details
     */
    public function preview(Media $media): View
    {
        $media->load('model');
        
        // Get related media from same collection
        $relatedMedia = Media::where('collection_name', $media->collection_name)
            ->where('model_type', $media->model_type)
            ->where('model_id', $media->model_id)
            ->where('id', '!=', $media->id)
            ->orderBy('order_column')
            ->orderBy('created_at')
            ->limit(10)
            ->get();

        return view('cauri-media::preview', compact('media', 'relatedMedia'));
    }

    /**
     * Show upload form
     */
    public function create(Request $request): View
    {
        $collections = Media::distinct()->pluck('collection_name')->sort();
        
        $modelType = $request->input('model_type');
        $modelId = $request->input('model_id');
        $collection = $request->input('collection', 'default');

        return view('cauri-media::create', compact(
            'collections', 
            'modelType', 
            'modelId', 
            'collection'
        ));
    }

    /**
     * Download media file
     */
    public function download(Media $media)
    {
        $path = $media->getPath();
        
        if (!\Storage::disk($media->disk)->exists($path)) {
            abort(404, 'File not found');
        }

        return \Storage::disk($media->disk)->download($path, $media->file_name);
    }

    /**
     * Get media info as JSON (for AJAX requests)
     */
    public function show(Media $media): JsonResponse
    {
        $media->load('model');
        
        return response()->json([
            'success' => true,
            'data' => [
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
                'custom_properties' => $media->custom_properties,
                'model' => $media->model ? [
                    'type' => get_class($media->model),
                    'id' => $media->model->getKey(),
                ] : null,
                'created_at' => $media->created_at,
                'updated_at' => $media->updated_at,
            ]
        ]);
    }

    /**
     * Get media collections for a specific model
     */
    public function collections(Request $request): JsonResponse
    {
        $request->validate([
            'model_type' => 'sometimes|string',
            'model_id' => 'sometimes|integer',
        ]);

        $query = Media::query();

        if ($request->filled('model_type')) {
            $query->where('model_type', $request->input('model_type'));
        }

        if ($request->filled('model_id')) {
            $query->where('model_id', $request->input('model_id'));
        }

        $collections = $query->select('collection_name')
            ->selectRaw('COUNT(*) as media_count')
            ->selectRaw('SUM(size) as total_size')
            ->groupBy('collection_name')
            ->orderBy('collection_name')
            ->get()
            ->map(function ($collection) {
                return [
                    'name' => $collection->collection_name,
                    'media_count' => $collection->media_count,
                    'total_size' => $collection->total_size,
                    'human_readable_size' => $this->humanFileSize($collection->total_size),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $collections
        ]);
    }

    /**
     * Get media statistics
     */
    public function stats(Request $request): JsonResponse
    {
        $request->validate([
            'model_type' => 'sometimes|string',
            'model_id' => 'sometimes|integer',
        ]);

        $query = Media::query();

        if ($request->filled('model_type')) {
            $query->where('model_type', $request->input('model_type'));
        }

        if ($request->filled('model_id')) {
            $query->where('model_id', $request->input('model_id'));
        }

        $totalFiles = $query->count();
        $totalSize = $query->sum('size');

        $byType = [
            'images' => $query->clone()->where('mime_type', 'like', 'image/%')->count(),
            'videos' => $query->clone()->where('mime_type', 'like', 'video/%')->count(),
            'audio' => $query->clone()->where('mime_type', 'like', 'audio/%')->count(),
            'documents' => $query->clone()->where('mime_type', 'like', 'application/%')->count(),
        ];

        $byCollection = $query->clone()
            ->selectRaw('collection_name, COUNT(*) as count, SUM(size) as size')
            ->groupBy('collection_name')
            ->pluck('count', 'collection_name')
            ->toArray();

        $recentUploads = $query->clone()
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get(['id', 'name', 'file_name', 'mime_type', 'size', 'created_at'])
            ->map(function ($media) {
                return [
                    'id' => $media->id,
                    'name' => $media->name,
                    'file_name' => $media->file_name,
                    'mime_type' => $media->mime_type,
                    'size' => $media->size,
                    'human_readable_size' => $this->humanFileSize($media->size),
                    'created_at' => $media->created_at,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'total_files' => $totalFiles,
                'total_size' => $totalSize,
                'total_size_human' => $this->humanFileSize($totalSize),
                'by_type' => $byType,
                'by_collection' => $byCollection,
                'recent_uploads' => $recentUploads,
            ]
        ]);
    }

    /**
     * Export media list as CSV
     */
    public function export(Request $request)
    {
        $request->validate([
            'collection' => 'sometimes|string',
            'type' => 'sometimes|string',
            'model_type' => 'sometimes|string',
            'model_id' => 'sometimes|integer',
        ]);

        $query = Media::with('model');

        // Apply same filters as index
        if ($request->filled('collection')) {
            $query->where('collection_name', $request->input('collection'));
        }

        if ($request->filled('type')) {
            $type = $request->input('type');
            $query->when($type === 'image', fn($q) => $q->where('mime_type', 'like', 'image/%'))
                  ->when($type === 'video', fn($q) => $q->where('mime_type', 'like', 'video/%'))
                  ->when($type === 'audio', fn($q) => $q->where('mime_type', 'like', 'audio/%'))
                  ->when($type === 'document', fn($q) => $q->where('mime_type', 'like', 'application/%'));
        }

        if ($request->filled('model_type')) {
            $query->where('model_type', $request->input('model_type'));
        }

        if ($request->filled('model_id')) {
            $query->where('model_id', $request->input('model_id'));
        }

        $media = $query->orderBy('created_at', 'desc')->get();

        $filename = 'media_export_' . date('Y-m-d_H-i-s') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($media) {
            $file = fopen('php://output', 'w');
            
            // CSV Header
            fputcsv($file, [
                'ID', 'Name', 'File Name', 'Collection', 'MIME Type', 
                'Size (bytes)', 'Size (human)', 'Model Type', 'Model ID', 
                'Created At', 'URL'
            ]);

            // CSV Data
            foreach ($media as $item) {
                fputcsv($file, [
                    $item->id,
                    $item->name,
                    $item->file_name,
                    $item->collection_name,
                    $item->mime_type,
                    $item->size,
                    $item->human_readable_size,
                    $item->model_type,
                    $item->model_id,
                    $item->created_at,
                    $item->getUrl(),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Convert file size to human readable format
     */
    protected function humanFileSize(int $bytes, int $decimals = 2): string
    {
        $size = ['B', 'kB', 'MB', 'GB', 'TB'];
        $factor = floor((strlen($bytes) - 1) / 3);
        return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . ' ' . @$size[$factor];
    }
}