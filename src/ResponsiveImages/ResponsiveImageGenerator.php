<?php

namespace Cauri\MediaLibrary\ResponsiveImages;

use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use Cauri\MediaLibrary\Models\Media;

class ResponsiveImageGenerator
{
    protected ImageManager $imageManager;
    protected array $breakpoints;
    protected array $formats;

    public function __construct()
    {
        $driver = config('cauri-media-library.image_driver', 'gd') === 'imagick' 
            ? new ImagickDriver() 
            : new GdDriver();
            
        $this->imageManager = new ImageManager($driver);
        $this->breakpoints = config('cauri-media-library.responsive_images.breakpoints', []);
        $this->formats = config('cauri-media-library.responsive_images.formats', ['webp', 'jpg']);
    }

    public function generate(Media $media): array
    {
        if (!$media->isImage()) {
            return [];
        }

        $inputPath = Storage::disk($media->disk)->path($media->getPath());
        
        if (!file_exists($inputPath)) {
            throw new \Exception("Source file not found: {$inputPath}");
        }

        $image = $this->imageManager->read($inputPath);
        $originalWidth = $image->width();
        $originalHeight = $image->height();

        $responsiveImages = [];

        foreach ($this->breakpoints as $width) {
            // Ne pas upscaler
            if ($width > $originalWidth) {
                continue;
            }

            $height = (int) round(($width / $originalWidth) * $originalHeight);

            foreach ($this->formats as $format) {
                $responsiveImage = $this->generateSingleResponsiveImage(
                    $media,
                    clone $image,
                    $width,
                    $height,
                    $format
                );

                if ($responsiveImage) {
                    $responsiveImages[] = $responsiveImage;
                }
            }
        }

        // Mettre à jour le média avec les images responsives
        $media->responsive_images = $responsiveImages;
        $media->save();

        return $responsiveImages;
    }

    protected function generateSingleResponsiveImage(
        Media $media,
        $image,
        int $width,
        int $height,
        string $format
    ): ?array {
        try {
            // Redimensionner
            $resizedImage = $image->scale($width, $height);

            // Définir la qualité selon le format
            $quality = match ($format) {
                'webp' => 85,
                'jpg', 'jpeg' => 85,
                'png' => 8,
                default => 85,
            };

            // Encoder
            $encoded = $resizedImage->encode($format, $quality);

            // Générer le chemin de sortie
            $pathInfo = pathinfo($media->file_name);
            $basename = $pathInfo['filename'];
            $outputFileName = "{$basename}-{$width}w.{$format}";
            
            $pathGenerator = app(config('cauri-media-library.path_generator'));
            $basePath = $pathGenerator->getPathForConversions($media);
            $outputPath = $basePath . 'responsive/' . $outputFileName;
            
            // Sauvegarder
            Storage::disk($media->disk)->put($outputPath, $encoded->toString());

            // Générer l'URL
            $url = Storage::disk($media->disk)->url($outputPath);

            return [
                'width' => $width,
                'height' => $height,
                'format' => $format,
                'url' => $url,
                'path' => $outputPath,
                'size' => strlen($encoded->toString()),
            ];

        } catch (\Exception $e) {
            \Log::error("Failed to generate responsive image: " . $e->getMessage());
            return null;
        }
    }

    public function generateTinyPlaceholder(Media $media): ?string
    {
        if (!$media->isImage() || !config('cauri-media-library.responsive_images.use_tiny_placeholders')) {
            return null;
        }

        try {
            $inputPath = Storage::disk($media->disk)->path($media->getPath());
            $image = $this->imageManager->read($inputPath);

            // Créer un placeholder 20x20 très flou
            $placeholder = $image->scale(20, 20);
            $encoded = $placeholder->toWebp(50);

            // Convertir en base64
            return 'data:image/webp;base64,' . base64_encode($encoded->toString());

        } catch (\Exception $e) {
            \Log::error("Failed to generate tiny placeholder: " . $e->getMessage());
            return null;
        }
    }
}