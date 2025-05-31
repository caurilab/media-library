<?php

namespace Cauri\MediaLibrary\FileAdder;

use Illuminate\Database\Eloquent\Model;

class FileAdderFactory
{
    public function create($file, ?Model $subject = null): FileAdder
    {
        $fileAdder = new FileAdder();
        
        if ($subject) {
            $fileAdder->setSubject($subject);
        }
        
        return $fileAdder->setFile($file);
    }
}