<?php

namespace Cauri\MediaLibrary\Facades;

use Illuminate\Support\Facades\Facade;

class FileUpload extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'cauri-file-upload';
    }
}