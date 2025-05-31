<?php

namespace Cauri\MediaLibrary\Exceptions;

use Exception;

class FileCannotBeAdded extends Exception
{
    public static function fileNotFound(string $path): self
    {
        return new static("File not found at path: {$path}");
    }

    public static function fileTooBig(int $size, int $maxSize): self
    {
        $sizeInMb = round($size / 1024 / 1024, 2);
        $maxSizeInMb = round($maxSize / 1024 / 1024, 2);
        
        return new static("File size ({$sizeInMb}MB) exceeds the maximum allowed size ({$maxSizeInMb}MB)");
    }

    public static function invalidMimeType(string $mimeType): self
    {
        return new static("MIME type '{$mimeType}' is not allowed");
    }

    public static function invalidUrl(string $url): self
    {
        return new static("Invalid URL provided: {$url}");
    }

    public static function couldNotDownload(string $url): self
    {
        return new static("Could not download file from URL: {$url}");
    }
}