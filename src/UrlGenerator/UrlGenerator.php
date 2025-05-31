<?php

namespace Cauri\MediaLibrary\UrlGenerator;

use Cauri\MediaLibrary\Models\Media;

interface UrlGenerator
{
    public function getUrl(Media $media, string $conversionName = ''): string;
}