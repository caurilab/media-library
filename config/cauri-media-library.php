<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    */
    'default_filesystem' => 'media',

    /*
    |--------------------------------------------------------------------------
    | Disk Name
    |--------------------------------------------------------------------------
    */
    'disk_name' => env('MEDIA_DISK', 'media'),

    /*
    |--------------------------------------------------------------------------
    | File Size Limits
    |--------------------------------------------------------------------------
    */
    'max_file_size' => 1024 * 1024 * 50, // 50MB
    'max_total_size' => 1024 * 1024 * 500, // 500MB total

    /*
    |--------------------------------------------------------------------------
    | Queue Configuration
    |--------------------------------------------------------------------------
    */
    'queue_name' => env('MEDIA_QUEUE', 'default'),
    'queue_conversions_by_default' => true,
    'queue_connection' => env('MEDIA_QUEUE_CONNECTION', 'default'),

    /*
    |--------------------------------------------------------------------------
    | Model Configuration
    |--------------------------------------------------------------------------
    */
    'media_model' => Cauri\MediaLibrary\Models\Media::class,

    /*
    |--------------------------------------------------------------------------
    | Generators
    |--------------------------------------------------------------------------
    */
    'path_generator' => Cauri\MediaLibrary\PathGenerator\DefaultPathGenerator::class,
    'url_generator' => Cauri\MediaLibrary\UrlGenerator\DefaultUrlGenerator::class,

    /*
    |--------------------------------------------------------------------------
    | Allowed File Types
    |--------------------------------------------------------------------------
    */
    'allowed_file_types' => [
        'image' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp', 'tiff'],
        'video' => ['mp4', 'avi', 'mov', 'wmv', 'webm', 'mkv', 'flv'],
        'audio' => ['mp3', 'wav', 'ogg', 'aac', 'flac', 'm4a'],
        'document' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'rtf'],
        'archive' => ['zip', 'rar', '7z', 'tar', 'gz'],
        'code' => ['js', 'css', 'html', 'php', 'json', 'xml', 'yaml', 'yml'],
    ],

    /*
    |--------------------------------------------------------------------------
    | MIME Type Restrictions
    |--------------------------------------------------------------------------
    */
    'allowed_mime_types' => [
        // Images
        'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml',
        'image/bmp', 'image/tiff',
        
        // Videos
        'video/mp4', 'video/quicktime', 'video/x-msvideo', 'video/webm',
        
        // Audio
        'audio/mpeg', 'audio/wav', 'audio/ogg', 'audio/aac',
        
        // Documents
        'application/pdf', 'application/msword', 
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        
        // Archives
        'application/zip', 'application/x-rar-compressed', 'application/x-7z-compressed',
    ],

    /*
    |--------------------------------------------------------------------------
    | Image Optimization
    |--------------------------------------------------------------------------
    */
    'image_optimizers' => [
        'jpg' => [
            'quality' => 85,
            'progressive' => true,
            'optimize_for' => 'web', // web, print, archive
        ],
        'png' => [
            'compression_level' => 6,
            'preserve_transparency' => true,
        ],
        'webp' => [
            'quality' => 85,
            'lossless' => false,
        ],
        'gif' => [
            'optimize' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Conversions
    |--------------------------------------------------------------------------
    */
    'conversions' => [
        'thumb' => [
            'width' => 300,
            'height' => 300,
            'quality' => 80,
            'format' => 'webp',
            'fit' => 'crop',
        ],
        'medium' => [
            'width' => 800,
            'height' => 600,
            'quality' => 85,
            'format' => 'webp',
            'fit' => 'contain',
        ],
        'large' => [
            'width' => 1920,
            'height' => 1080,
            'quality' => 90,
            'format' => 'webp',
            'fit' => 'contain',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Responsive Images
    |--------------------------------------------------------------------------
    */
    'responsive_images' => [
        'use_tiny_placeholders' => true,
        'tiny_placeholder_generator' => null,
        'breakpoints' => [320, 480, 768, 1024, 1440, 1920],
        'formats' => ['webp', 'jpg'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance & Caching
    |--------------------------------------------------------------------------
    */
    'enable_vapor' => false,
    'cache_conversions' => true,
    'cache_ttl' => 60 * 60 * 24 * 7, // 7 days

    /*
    |--------------------------------------------------------------------------
    | Temporary Directory
    |--------------------------------------------------------------------------
    */
    'temporary_directory_path' => storage_path('app/temp'),

    /*
    |--------------------------------------------------------------------------
    | Image Driver
    |--------------------------------------------------------------------------
    */
    'image_driver' => env('IMAGE_DRIVER', 'gd'), // gd, imagick

    /*
    |--------------------------------------------------------------------------
    | Security
    |--------------------------------------------------------------------------
    */
    'sanitize_filenames' => true,
    'prevent_duplicates' => false,
    'hash_names' => false,

    /*
    |--------------------------------------------------------------------------
    | Frontend Configuration
    |--------------------------------------------------------------------------
    */
    'vue_components' => [
        'auto_register' => false,
        'prefix' => 'Cauri',
        'styles_path' => 'css/cauri-media',
    ],

    /*
    |--------------------------------------------------------------------------
    | API Configuration
    |--------------------------------------------------------------------------
    */
    'api' => [
        'prefix' => 'cauri-media',
        'middleware' => ['web'],
        'rate_limit' => 60, // requests per minute
    ],

    /*
    |--------------------------------------------------------------------------
    | Validation Rules
    |--------------------------------------------------------------------------
    */
    'validation' => [
        'image_dimensions' => [
            'min_width' => 50,
            'min_height' => 50,
            'max_width' => 8000,
            'max_height' => 8000,
        ],
        'video_duration' => [
            'max_seconds' => 600, // 10 minutes
        ],
    ],
];