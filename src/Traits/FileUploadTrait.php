<?php

namespace Cauri\MediaLibrary\Traits;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\Encoders\WebpEncoder;
use Cauri\MediaLibrary\Models\Media;

trait FileUploadTrait
{
    protected string $currentYear;
    protected string $currentMonth;
    protected ImageManager $imageManager;

    public function initializeFileUploadTrait()
    {
        $this->currentYear = date('/Y');
        $this->currentMonth = date('/m');
        
        $driver = config('cauri-media-library.image_driver', 'gd') === 'imagick' 
            ? new ImagickDriver() 
            : new GdDriver();
            
        $this->imageManager = new ImageManager($driver);
    }

    /**
     * Save file with automatic conversions for CAURI Media Library
     */
    public function saveFileWithConversions($file, string $title, ?object $model = null, string $collection = 'default'): Media
    {
        $this->initializeFileUploadTrait();
        
        if ($model && method_exists($model, 'addMedia')) {
            // Utiliser le système CAURI Media Library
            return $model->addMedia($file)
                ->usingName($title)
                ->toMediaCollection($collection);
        }
        
        // Fallback: création manuelle
        return $this->createMediaManually($file, $title, $model, $collection);
    }

    /**
     * Save multiple files from request with automatic processing
     */
    public function saveRequestFiles(Request $request, ?object $model = null, array $fileFields = []): array
    {
        $this->initializeFileUploadTrait();
        $savedMedia = [];
        
        foreach ($request->all() as $key => $value) {
            if ($request->hasFile($key)) {
                // Si c'est dans la liste des champs à traiter ou si la liste est vide (traiter tous)
                if (empty($fileFields) || in_array($key, $fileFields)) {
                    $files = is_array($request->file($key)) 
                        ? $request->file($key) 
                        : [$request->file($key)];
                    
                    foreach ($files as $file) {
                        $title = $this->generateTitleFromFile($file);
                        $collection = $this->getCollectionFromFieldName($key);
                        
                        $media = $this->saveFileWithConversions($file, $title, $model, $collection);
                        $savedMedia[$key][] = $media;
                    }
                }
            }
        }
        
        return $savedMedia;
    }

    /**
     * Save files with custom image sizes (like your original trait)
     */
    public function saveFileWithCustomSizes($file, string $title, array $sizes = [], ?object $model = null): Media
    {
        $this->initializeFileUploadTrait();
        
        // Sizes par défaut inspirés de votre code
        if (empty($sizes)) {
            $sizes = [
                ['width' => 150, 'height' => 150, 'name' => 'thumb'],
                ['width' => 300, 'height' => 300, 'name' => 'small'],
                ['width' => 600, 'height' => 400, 'name' => 'medium'],
                ['width' => 1200, 'height' => 800, 'name' => 'large'],
                ['width' => 1920, 'height' => 1080, 'name' => 'xlarge'],
            ];
        }
        
        $pathInfo = pathinfo($file->getClientOriginalName());
        $filename = Str::slug($title ?: $pathInfo['filename']);
        $extension = $pathInfo['extension'] ?? 'jpg';
        
        // Créer le média principal
        $media = $this->createMediaRecord($file, $title, $model);
        
        // Générer les conversions personnalisées
        $this->generateCustomConversions($file, $media, $sizes, $filename, $extension);
        
        return $media;
    }

    /**
     * Save files from seeder (adaptation de votre méthode saveFileSeeder)
     */
    public function saveFileFromPath(string $imagePath, string $title, ?object $model = null, string $collection = 'default'): Media
    {
        $this->initializeFileUploadTrait();
        
        if (!file_exists($imagePath)) {
            throw new \Exception("File not found: {$imagePath}");
        }
        
        // Créer un UploadedFile temporaire à partir du chemin
        $tempFile = $this->createUploadedFileFromPath($imagePath);
        
        return $this->saveFileWithConversions($tempFile, $title, $model, $collection);
    }

    /**
     * Save logos (adaptation de votre méthode saveLogos)
     */
    public function saveLogos(Request $request, ?object $model = null): array
    {
        return $this->saveRequestFiles($request, $model, ['logo', 'favicon', 'brand_logo']);
    }

    /**
     * Process image with max width/height constraints
     */
    public function processImageWithConstraints($file, int $maxWidth, int $maxHeight): string
    {
        $this->initializeFileUploadTrait();
        
        $image = $this->imageManager->read($file);
        $width = $image->width();
        $height = $image->height();
        
        // Logique de redimensionnement adaptée de votre code
        if ($width > $maxWidth && $height > $maxHeight) {
            $image = $image->scale($maxWidth, $maxHeight);
        } elseif ($width > $maxWidth) {
            $image = $image->scaleDown($maxWidth);
        } elseif ($height > $maxHeight) {
            $image = $image->scaleDown(height: $maxHeight);
        }
        
        // Sauvegarder temporairement
        $tempPath = storage_path('app/temp/' . uniqid() . '.jpg');
        $this->ensureDirectoryExists(dirname($tempPath));
        
        $encoder = new JpegEncoder(quality: 85);
        $encoded = $image->encode($encoder);
        file_put_contents($tempPath, $encoded->toString());
        
        return $tempPath;
    }

    /**
     * Generate responsive images (inspiré de votre boucle for)
     */
    public function generateResponsiveImages($file, string $baseName, array $breakpoints = []): array
    {
        $this->initializeFileUploadTrait();
        
        if (empty($breakpoints)) {
            $breakpoints = [320, 480, 768, 1024, 1440, 1920];
        }
        
        $image = $this->imageManager->read($file);
        $originalWidth = $image->width();
        $originalHeight = $image->height();
        $generatedImages = [];
        
        foreach ($breakpoints as $width) {
            if ($width > $originalWidth) continue;
            
            $height = (int) round(($width / $originalWidth) * $originalHeight);
            $resizedImage = clone $image->scale($width, $height);
            
            $fileName = "{$baseName}-{$width}w.webp";
            $path = $this->getResponsiveImagePath($fileName);
            
            $this->ensureDirectoryExists(dirname($path));
            
            $encoder = new WebpEncoder(quality: 85);
            $encoded = $resizedImage->encode($encoder);
            file_put_contents($path, $encoded->toString());
            
            $generatedImages[] = [
                'width' => $width,
                'height' => $height,
                'path' => $path,
                'url' => $this->getResponsiveImageUrl($fileName),
                'size' => strlen($encoded->toString()),
            ];
        }
        
        return $generatedImages;
    }

    /**
     * Create media record manually
     */
    protected function createMediaManually($file, string $title, ?object $model, string $collection): Media
    {
        $mediaClass = config('cauri-media-library.media_model');
        
        $media = new $mediaClass([
            'model_type' => $model ? get_class($model) : null,
            'model_id' => $model?->getKey(),
            'collection_name' => $collection,
            'name' => $title,
            'file_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'disk' => config('cauri-media-library.disk_name'),
            'size' => $file->getSize(),
            'custom_properties' => [],
            'uuid' => Str::uuid(),
        ]);
        
        $media->save();
        
        // Sauvegarder le fichier
        $pathGenerator = app(config('cauri-media-library.path_generator'));
        $path = $pathGenerator->getPath($media);
        
        \Storage::disk($media->disk)->putFileAs(
            dirname($path),
            $file,
            basename($path)
        );
        
        return $media;
    }

    /**
     * Generate custom conversions
     */
    protected function generateCustomConversions($file, Media $media, array $sizes, string $filename, string $extension): void
    {
        $image = $this->imageManager->read($file);
        
        foreach ($sizes as $size) {
            $resizedImage = clone $image->cover($size['width'], $size['height']);
            
            $conversionName = $size['name'];
            $conversionFileName = "{$filename}-{$size['width']}w.{$extension}";
            
            $pathGenerator = app(config('cauri-media-library.path_generator'));
            $conversionPath = $pathGenerator->getPath($media, $conversionName);
            $fullPath = \Storage::disk($media->disk)->path($conversionPath);
            
            $this->ensureDirectoryExists(dirname($fullPath));
            
            $encoder = $extension === 'jpg' || $extension === 'jpeg' 
                ? new JpegEncoder(quality: 85)
                : new WebpEncoder(quality: 85);
                
            $encoded = $resizedImage->encode($encoder);
            file_put_contents($fullPath, $encoded->toString());
            
            // Marquer la conversion comme générée
            $media->markAsConversionGenerated($conversionName, true);
        }
        
        $media->save();
    }

    /**
     * Create UploadedFile from file path
     */
    protected function createUploadedFileFromPath(string $path): UploadedFile
    {
        $name = basename($path);
        $mimeType = mime_content_type($path);
        
        return new UploadedFile($path, $name, $mimeType, null, true);
    }

    /**
     * Generate title from file
     */
    protected function generateTitleFromFile(UploadedFile $file): string
    {
        $pathInfo = pathinfo($file->getClientOriginalName());
        return Str::title(str_replace(['-', '_'], ' ', $pathInfo['filename']));
    }

    /**
     * Get collection name from field name
     */
    protected function getCollectionFromFieldName(string $fieldName): string
    {
        $collectionMap = [
            'image' => 'images',
            'logo' => 'logos',
            'avatar' => 'avatars',
            'gallery' => 'gallery',
            'document' => 'documents',
            'pdf' => 'documents',
            'audio' => 'audio',
            'video' => 'videos',
        ];
        
        foreach ($collectionMap as $key => $collection) {
            if (str_contains($fieldName, $key)) {
                return $collection;
            }
        }
        
        return 'default';
    }

    /**
     * Get responsive image path
     */
    protected function getResponsiveImagePath(string $fileName): string
    {
        return storage_path("app/media/responsive/{$this->currentYear}{$this->currentMonth}/{$fileName}");
    }

    /**
     * Get responsive image URL
     */
    protected function getResponsiveImageUrl(string $fileName): string
    {
        return asset("storage/media/responsive{$this->currentYear}{$this->currentMonth}/{$fileName}");
    }

    /**
     * Ensure directory exists
     */
    protected function ensureDirectoryExists(string $path): void
    {
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
    }

    /**
     * Create media record
     */
    protected function createMediaRecord($file, string $title, ?object $model): Media
    {
        $mediaClass = config('cauri-media-library.media_model');
        
        return $mediaClass::create([
            'model_type' => $model ? get_class($model) : null,
            'model_id' => $model?->getKey(),
            'collection_name' => 'default',
            'name' => $title,
            'file_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'disk' => config('cauri-media-library.disk_name'),
            'size' => $file->getSize(),
            'custom_properties' => [],
            'uuid' => Str::uuid(),
        ]);
    }
}