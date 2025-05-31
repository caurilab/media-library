<?php

// src/Support/helpers.php

if (!function_exists('make_storage_dir')) {
    /**
     * Create storage directories (adaptation de votre fonction)
     */
    function make_storage_dir(): void
    {
        $year = date('/Y');
        $month = date('/m');
        
        $directories = [
            "storage/uploads/images{$year}{$month}",
            "storage/uploads/images{$year}{$month}/resizing",
            "storage/uploads/images{$year}{$month}/origin",
            "storage/uploads/medias{$year}{$month}",
            "storage/logos",
        ];
        
        foreach ($directories as $dir) {
            $fullPath = public_path($dir);
            if (!file_exists($fullPath)) {
                mkdir($fullPath, 0755, true);
            }
        }
    }
}

if (!function_exists('get_image_size')) {
    /**
     * Get image size configuration (adaptation de votre fonction)
     */
    function get_image_size(int $level, bool $asArray = false)
    {
        $sizes = [
            1 => ['width' => 150, 'height' => 150, 'name' => 'thumb'],
            2 => ['width' => 300, 'height' => 300, 'name' => 'small'],
            3 => ['width' => 600, 'height' => 400, 'name' => 'medium'],
            4 => ['width' => 1200, 'height' => 800, 'name' => 'large'],
            5 => ['width' => 1920, 'height' => 1080, 'name' => 'xlarge'],
        ];
        
        $size = $sizes[$level] ?? $sizes[3];
        
        if ($asArray) {
            return [$size['width'], $size['height']];
        }
        
        return "-{$size['width']}w";
    }
}

if (!function_exists('cauri_media')) {
    /**
     * Helper pour utiliser CAURI Media Library facilement
     */
    function cauri_media(): \Cauri\MediaLibrary\MediaLibrary
    {
        return app('cauri-media-library');
    }
}

if (!function_exists('upload_file_simple')) {
    /**
     * Helper simple pour uploader un fichier
     */
    function upload_file_simple($file, string $title, $model = null, string $collection = 'default'): \Cauri\MediaLibrary\Models\Media
    {
        if ($model && method_exists($model, 'addMedia')) {
            return $model->addMedia($file)
                ->usingName($title)
                ->toMediaCollection($collection);
        }
        
        // Créer directement
        $mediaClass = config('cauri-media-library.media_model');
        return $mediaClass::create([
            'name' => $title,
            'file_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'disk' => config('cauri-media-library.disk_name'),
            'size' => $file->getSize(),
            'collection_name' => $collection,
            'model_type' => $model ? get_class($model) : null,
            'model_id' => $model?->getKey(),
        ]);
    }
}

