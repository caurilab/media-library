<?php

namespace Cauri\MediaLibrary\PathGenerator;

use Cauri\MediaLibrary\Models\Media;

interface PathGenerator
{
    public function getPath(Media $media, string $conversionName = ''): string;
    public function getPathForConversions(Media $media): string;
}