<?php

namespace Cauri\MediaLibrary\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Cauri\MediaLibrary\Models\Media;

class MediaHasBeenDeleted
{
    use Dispatchable, SerializesModels;

    public array $mediaData;

    public function __construct(Media $media)
    {
        // Stocker les données nécessaires car l'objet sera supprimé
        $this->mediaData = [
            'id' => $media->id,
            'disk' => $media->disk,
            'file_name' => $media->file_name,
            'generated_conversions' => $media->generated_conversions,
            'model_type' => $media->model_type,
            'model_id' => $media->model_id,
            'collection_name' => $media->collection_name,
        ];
    }
}