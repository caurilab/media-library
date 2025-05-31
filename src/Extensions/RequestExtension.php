<?php

namespace Cauri\MediaLibrary\Extensions;

use Illuminate\Http\Request;
use Cauri\MediaLibrary\Traits\FileUploadTrait;

class RequestExtension
{
    use FileUploadTrait;
    
    /**
     * Extend Request with media upload capabilities
     */
    public static function extend(): void
    {
        Request::macro('saveMedia', function ($model = null, array $fields = []) {
            $extension = new static();
            return $extension->saveRequestFiles($this, $model, $fields);
        });
        
        Request::macro('saveLogos', function ($model = null) {
            $extension = new static();
            return $extension->saveLogos($this, $model);
        });
        
        Request::macro('hasAnyFile', function (array $fields = []) {
            if (empty($fields)) {
                foreach ($this->all() as $key => $value) {
                    if ($this->hasFile($key)) {
                        return true;
                    }
                }
                return false;
            }
            
            foreach ($fields as $field) {
                if ($this->hasFile($field)) {
                    return true;
                }
            }
            
            return false;
        });
    }
}