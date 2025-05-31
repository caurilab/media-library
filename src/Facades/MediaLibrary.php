<?php

namespace Cauri\MediaLibrary\Facades;

use Illuminate\Support\Facades\Facade;

class MediaLibrary extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'cauri-media-library';
    }
}