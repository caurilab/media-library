<?php

namespace Cauri\MediaLibrary;

use Illuminate\Database\Eloquent\Model;
use Cauri\MediaLibrary\FileAdder\FileAdderFactory;
use Cauri\MediaLibrary\Models\Media;

class MediaLibrary
{
    protected FileAdderFactory $fileAdderFactory;

    public function __construct(FileAdderFactory $fileAdderFactory)
    {
        $this->fileAdderFactory = $fileAdderFactory;
    }

    public function addMedia($file, Model|null $model)
    {
        return $this->fileAdderFactory->create($file, $model);
    }

    public function getMediaModel(): string
    {
        return config('cauri-media-library.media_model');
    }

    public function getPathGenerator(): string
    {
        return config('cauri-media-library.path_generator');
    }

    public function getUrlGenerator(): string
    {
        return config('cauri-media-library.url_generator');
    }

    public function getAllowedMimeTypes(): array
    {
        return config('cauri-media-library.allowed_mime_types', []);
    }

    public function getAllowedFileTypes(): array
    {
        return config('cauri-media-library.allowed_file_types', []);
    }

    public function getMaxFileSize(): int
    {
        return config('cauri-media-library.max_file_size', 1024 * 1024 * 10);
    }

    public function getDiskName(): string
    {
        return config('cauri-media-library.disk_name', 'media');
    }

    public function shouldQueueConversions(): bool
    {
        return config('cauri-media-library.queue_conversions_by_default', true);
    }

    public function version(): string
    {
        return '1.0.0';
    }
}