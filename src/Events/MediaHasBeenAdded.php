<?php

namespace Cauri\MediaLibrary\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Cauri\MediaLibrary\Models\Media;

class MediaHasBeenAdded
{
    use Dispatchable, SerializesModels;

    public Media $media;

    public function __construct(Media $media)
    {
        $this->media = $media;
    }
}