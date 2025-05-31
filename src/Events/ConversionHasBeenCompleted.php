<?php

namespace Cauri\MediaLibrary\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Cauri\MediaLibrary\Models\Media;
use Cauri\MediaLibrary\Conversions\Conversion;

class ConversionHasBeenCompleted
{
    use Dispatchable, SerializesModels;

    public Media $media;
    public Conversion $conversion;

    public function __construct(Media $media, Conversion $conversion)
    {
        $this->media = $media;
        $this->conversion = $conversion;
    }
}