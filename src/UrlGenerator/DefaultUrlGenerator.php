<?php

namespace Cauri\MediaLibrary\UrlGenerator;

use Illuminate\Support\Facades\Storage;
use Cauri\MediaLibrary\Models\Media;

class DefaultUrlGenerator implements UrlGenerator
{
    public function getUrl(Media $media, string $conversionName = ''): string
    {
        $pathGenerator = app(config('cauri-media-library.path_generator'));
        $path = $pathGenerator->getPath($media, $conversionName);

        // Check if conversion exists, fallback to original if not
        if ($conversionName !== '' && !Storage::disk($media->disk)->exists($path)) {
            if (!$media->hasGeneratedConversion($conversionName)) {
                // Return original file URL if conversion doesn't exist
                $path = $pathGenerator->getPath($media);
            }
        }

        return Storage::disk($media->disk)->url($path);
    }

    public function getTemporaryUrl(Media $media, \DateTimeInterface $expiration, string $conversionName = ''): string
    {
        $pathGenerator = app(config('cauri-media-library.path_generator'));
        $path = $pathGenerator->getPath($media, $conversionName);

        $disk = Storage::disk($media->disk);
        
        if (method_exists($disk, 'temporaryUrl')) {
            return $disk->temporaryUrl($path, $expiration);
        }

        // Fallback to regular URL if temporary URLs are not supported
        return $this->getUrl($media, $conversionName);
    }
}