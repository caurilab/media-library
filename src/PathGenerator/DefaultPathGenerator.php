<?php

namespace Cauri\MediaLibrary\PathGenerator;

use Cauri\MediaLibrary\Models\Media;

class DefaultPathGenerator implements PathGenerator
{
    public function getPath(Media $media, string $conversionName = ''): string
    {
        $basePath = $this->getBasePath($media);
        
        if ($conversionName === '') {
            return $basePath . '/' . $media->file_name;
        }

        return $basePath . '/conversions/' . $this->getConversionFileName($media, $conversionName);
    }

    public function getPathForConversions(Media $media): string
    {
        return $this->getBasePath($media) . '/conversions/';
    }

    protected function getBasePath(Media $media): string
    {
        // Structure: model_type/model_id/collection_name/media_id
        return implode('/', [
            $this->sanitizeDirectoryName(class_basename($media->model_type)),
            $media->model_id,
            $media->collection_name,
            $media->id,
        ]);
    }

    protected function getConversionFileName(Media $media, string $conversionName): string
    {
        $pathInfo = pathinfo($media->file_name);
        $baseName = $pathInfo['filename'];
        
        // Get the conversion format or keep original extension
        $extension = $this->getConversionExtension($conversionName, $pathInfo['extension'] ?? '');
        
        return $baseName . '-' . $conversionName . '.' . $extension;
    }

    protected function getConversionExtension(string $conversionName, string $originalExtension): string
    {
        $conversions = config('cauri-media-library.conversions', []);
        
        if (isset($conversions[$conversionName]['format'])) {
            return $conversions[$conversionName]['format'];
        }

        // Default to WebP for images, keep original for others
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'tiff'];
        
        if (in_array(strtolower($originalExtension), $imageExtensions)) {
            return 'webp';
        }

        return $originalExtension;
    }

    protected function sanitizeDirectoryName(string $name): string
    {
        return preg_replace('/[^a-zA-Z0-9\-_]/', '_', $name);
    }
}