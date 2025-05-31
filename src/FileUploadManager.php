<?php

namespace Cauri\MediaLibrary;

use Cauri\MediaLibrary\Traits\FileUploadTrait;

class FileUploadManager
{
    use FileUploadTrait;
    
    public function __construct()
    {
        $this->initializeFileUploadTrait();
    }
}