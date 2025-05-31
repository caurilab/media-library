<?php

namespace Cauri\MediaLibrary\Conversions\ImageGenerators;

use Illuminate\Support\Facades\Storage;
use Intervention\Image\Encoders\AutoEncoder;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\Encoders\PngEncoder;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\Encoders\GifEncoder;
use Cauri\MediaLibrary\Models\Media;
use Cauri\MediaLibrary\Conversions\Conversion;

class Image extends ImageGenerator
{
    public function canHandle(string $mimeType): bool
    {
        return str_starts_with($mimeType, 'image/') && $mimeType !== 'image/svg+xml';
    }

    public function convert(Media $media, Conversion $conversion): string
    {
        $inputPath = Storage::disk($media->disk)->path($media->getPath());
        
        if (!file_exists($inputPath)) {
            throw new \Exception("Source file not found: {$inputPath}");
        }

        // Charger l'image
        $image = $this->imageManager->read($inputPath);

        // Appliquer le redimensionnement
        $image = $this->applyResize($image, $conversion);

        // Déterminer le format de sortie
        $outputFormat = $this->getOutputFormat($conversion, $media->extension);
        
        // Créer l'encoder approprié
        $encoder = $this->createEncoder($outputFormat, $conversion->getQuality());

        // Encoder l'image
        $encodedImage = $image->encode($encoder);

        // Sauvegarder
        $outputPath = $this->getOutputPath($media, $conversion);
        $outputFullPath = Storage::disk($media->disk)->path($outputPath);
        
        // Créer le répertoire si nécessaire
        $outputDir = dirname($outputFullPath);
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        file_put_contents($outputFullPath, $encodedImage->toString());

        // Optimiser l'image si activé
        if ($conversion->isOptimized()) {
            $this->optimizeImage($outputFullPath, $outputFormat);
        }

        return $outputPath;
    }

    protected function createEncoder(string $format, int $quality)
    {
        return match (strtolower($format)) {
            'jpg', 'jpeg' => new JpegEncoder(quality: $quality, progressive: true),
            'png' => new PngEncoder(),
            'webp' => new WebpEncoder(quality: $quality),
            'gif' => new GifEncoder(),
            default => new AutoEncoder(quality: $quality),
        };
    }

    protected function getOutputFormat(Conversion $conversion, string $originalFormat): string
    {
        if ($conversion->getFormat()) {
            return $conversion->getFormat();
        }

        // Par défaut, convertir les images en WebP pour une meilleure compression
        $webpSupported = ['jpg', 'jpeg', 'png'];
        if (in_array(strtolower($originalFormat), $webpSupported)) {
            return 'webp';
        }

        return $originalFormat;
    }

    protected function optimizeImage(string $path, string $format): void
    {
        if (!class_exists(\Spatie\ImageOptimizer\OptimizerChain::class)) {
            return;
        }

        try {
            $optimizerChain = \Spatie\ImageOptimizer\OptimizerChainFactory::create();
            $optimizerChain->optimize($path);
        } catch (\Exception $e) {
            // Log l'erreur mais ne pas échouer
            \Log::warning("Image optimization failed: " . $e->getMessage());
        }
    }
}