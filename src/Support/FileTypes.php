<?php

namespace Cauri\MediaLibrary\Support;

class FileTypes
{
    public static function getTypeFromMimeType(string $mimeType): string
    {
        $typeMap = [
            'image' => [
                'image/jpeg', 'image/png', 'image/gif', 'image/webp', 
                'image/svg+xml', 'image/bmp', 'image/tiff'
            ],
            'video' => [
                'video/mp4', 'video/quicktime', 'video/x-msvideo', 
                'video/webm', 'video/x-ms-wmv'
            ],
            'audio' => [
                'audio/mpeg', 'audio/wav', 'audio/ogg', 'audio/aac',
                'audio/flac', 'audio/x-m4a'
            ],
            'document' => [
                'application/pdf', 'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'text/plain', 'text/csv'
            ],
            'archive' => [
                'application/zip', 'application/x-rar-compressed',
                'application/x-7z-compressed', 'application/x-tar',
                'application/gzip'
            ],
        ];

        foreach ($typeMap as $type => $mimeTypes) {
            if (in_array($mimeType, $mimeTypes)) {
                return $type;
            }
        }

        return 'file';
    }

    public static function getExtensionFromMimeType(string $mimeType): string
    {
        $extensionMap = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'image/svg+xml' => 'svg',
            'image/bmp' => 'bmp',
            'image/tiff' => 'tiff',
            'video/mp4' => 'mp4',
            'video/quicktime' => 'mov',
            'video/x-msvideo' => 'avi',
            'video/webm' => 'webm',
            'audio/mpeg' => 'mp3',
            'audio/wav' => 'wav',
            'audio/ogg' => 'ogg',
            'application/pdf' => 'pdf',
            'application/zip' => 'zip',
            'text/plain' => 'txt',
            'text/csv' => 'csv',
        ];

        return $extensionMap[$mimeType] ?? 'bin';
    }

    public static function getMimeTypeFromExtension(string $extension): string
    {
        $mimeMap = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            'bmp' => 'image/bmp',
            'tiff' => 'image/tiff',
            'mp4' => 'video/mp4',
            'mov' => 'video/quicktime',
            'avi' => 'video/x-msvideo',
            'webm' => 'video/webm',
            'mp3' => 'audio/mpeg',
            'wav' => 'audio/wav',
            'ogg' => 'audio/ogg',
            'pdf' => 'application/pdf',
            'zip' => 'application/zip',
            'txt' => 'text/plain',
            'csv' => 'text/csv',
        ];

        return $mimeMap[strtolower($extension)] ?? 'application/octet-stream';
    }

    public static function isImage(string $mimeType): bool
    {
        return str_starts_with($mimeType, 'image/');
    }

    public static function isVideo(string $mimeType): bool
    {
        return str_starts_with($mimeType, 'video/');
    }

    public static function isAudio(string $mimeType): bool
    {
        return str_starts_with($mimeType, 'audio/');
    }

    public static function getAllowedExtensions(): array
    {
        $allowedTypes = config('cauri-media-library.allowed_file_types', []);
        
        return collect($allowedTypes)->flatten()->unique()->toArray();
    }

    public static function validateFileType(string $extension): bool
    {
        return in_array(strtolower($extension), self::getAllowedExtensions());
    }
}