<?php

namespace Cauri\MediaLibrary\Conversions\ImageGenerators;

use Illuminate\Support\Facades\Storage;
use Cauri\MediaLibrary\Models\Media;
use Cauri\MediaLibrary\Conversions\Conversion;

class Video extends ImageGenerator
{
    public function canHandle(string $mimeType): bool
    {
        return str_starts_with($mimeType, 'video/');
    }

    public function convert(Media $media, Conversion $conversion): string
    {
        // Pour les vidéos, on génère généralement des thumbnails
        return $this->generateVideoThumbnail($media, $conversion);
    }

    /**
     * Generate thumbnail from video
     */
    protected function generateVideoThumbnail(Media $media, Conversion $conversion): string
    {
        $inputPath = Storage::disk($media->disk)->path($media->getPath());
        
        if (!file_exists($inputPath)) {
            throw new \Exception("Source video file not found: {$inputPath}");
        }

        // Générer le chemin de sortie pour le thumbnail
        $outputPath = $this->getOutputPath($media, $conversion);
        $outputFullPath = Storage::disk($media->disk)->path($outputPath);
        
        // Créer le répertoire si nécessaire
        $outputDir = dirname($outputFullPath);
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        // Méthode 1: Utiliser FFmpeg si disponible
        if ($this->isFfmpegAvailable()) {
            return $this->extractThumbnailWithFfmpeg($inputPath, $outputFullPath, $conversion);
        }

        // Méthode 2: Fallback - créer une image placeholder
        return $this->createVideoPlaceholder($media, $conversion, $outputFullPath);
    }

    /**
     * Extract thumbnail using FFmpeg
     */
    protected function extractThumbnailWithFfmpeg(string $inputPath, string $outputPath, Conversion $conversion): string
    {
        $width = $conversion->getWidth() ?: 640;
        $height = $conversion->getHeight() ?: 480;
        $quality = $conversion->getQuality();

        // Commande FFmpeg pour extraire une frame à 00:00:01
        $command = sprintf(
            'ffmpeg -i %s -ss 00:00:01 -vframes 1 -vf "scale=%d:%d" -q:v %d %s 2>/dev/null',
            escapeshellarg($inputPath),
            $width,
            $height,
            $this->mapQualityToFfmpeg($quality),
            escapeshellarg($outputPath)
        );

        exec($command, $output, $returnCode);

        if ($returnCode !== 0 || !file_exists($outputPath)) {
            throw new \Exception("Failed to extract video thumbnail with FFmpeg");
        }

        return $this->getRelativeOutputPath($outputPath);
    }

    /**
     * Create video placeholder image
     */
    protected function createVideoPlaceholder(Media $media, Conversion $conversion, string $outputPath): string
    {
        $width = $conversion->getWidth() ?: 640;
        $height = $conversion->getHeight() ?: 480;

        // Créer une image placeholder avec Intervention Image
        $image = $this->imageManager->create($width, $height);
        
        // Remplir avec une couleur de fond
        $image->fill('#2d3748'); // Couleur gris foncé
        
        // Ajouter un icône play au centre (optionnel)
        $this->addPlayIcon($image, $width, $height);

        // Sauvegarder
        $encoder = new \Intervention\Image\Encoders\JpegEncoder(quality: $conversion->getQuality());
        $encoded = $image->encode($encoder);
        file_put_contents($outputPath, $encoded->toString());

        return $this->getRelativeOutputPath($outputPath);
    }

    /**
     * Add play icon to placeholder
     */
    protected function addPlayIcon($image, int $width, int $height): void
    {
        try {
            // Créer un triangle pour l'icône play
            $centerX = $width / 2;
            $centerY = $height / 2;
            $size = min($width, $height) * 0.15; // 15% de la taille minimale

            // Points du triangle (icône play)
            $points = [
                $centerX - $size/2, $centerY - $size/2,
                $centerX + $size/2, $centerY,
                $centerX - $size/2, $centerY + $size/2,
            ];

            // Note: Cette partie dépend de l'API Intervention Image v3
            // Il faudra adapter selon la version exacte utilisée
            
        } catch (\Exception $e) {
            // Ignorer si on ne peut pas ajouter l'icône
        }
    }

    /**
     * Check if FFmpeg is available
     */
    protected function isFfmpegAvailable(): bool
    {
        exec('ffmpeg -version 2>/dev/null', $output, $returnCode);
        return $returnCode === 0;
    }

    /**
     * Map quality percentage to FFmpeg quality scale
     */
    protected function mapQualityToFfmpeg(int $quality): int
    {
        // FFmpeg utilise une échelle inverse (2 = haute qualité, 31 = basse qualité)
        return max(2, min(31, 31 - round(($quality / 100) * 29)));
    }

    /**
     * Get relative path from absolute path
     */
    protected function getRelativeOutputPath(string $absolutePath): string
    {
        // Convertir le chemin absolu en chemin relatif pour le storage
        $storagePath = Storage::disk(config('cauri-media-library.disk_name'))->path('');
        return str_replace($storagePath, '', $absolutePath);
    }

    /**
     * Get video duration (if needed for future features)
     */
    public function getVideoDuration(string $videoPath): ?float
    {
        if (!$this->isFfmpegAvailable()) {
            return null;
        }

        $command = sprintf(
            'ffprobe -v quiet -show_entries format=duration -of csv="p=0" %s 2>/dev/null',
            escapeshellarg($videoPath)
        );

        $output = shell_exec($command);
        
        return $output ? (float) trim($output) : null;
    }

    /**
     * Get video dimensions (if needed for future features)
     */
    public function getVideoDimensions(string $videoPath): ?array
    {
        if (!$this->isFfmpegAvailable()) {
            return null;
        }

        $command = sprintf(
            'ffprobe -v quiet -select_streams v:0 -show_entries stream=width,height -of csv="s=x:p=0" %s 2>/dev/null',
            escapeshellarg($videoPath)
        );

        $output = shell_exec($command);
        
        if ($output) {
            $dimensions = explode('x', trim($output));
            if (count($dimensions) === 2) {
                return [
                    'width' => (int) $dimensions[0],
                    'height' => (int) $dimensions[1],
                ];
            }
        }

        return null;
    }
}