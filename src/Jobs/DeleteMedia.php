<?php

namespace Cauri\MediaLibrary\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class DeleteMedia implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 60;
    public int $tries = 3;

    protected array $mediaData;

    public function __construct(array $mediaData)
    {
        $this->mediaData = $mediaData;
        $this->onQueue(config('cauri-media-library.queue_name', 'default'));
    }

    public function handle(): void
    {
        $disk = $this->mediaData['disk'];
        $pathGenerator = app(config('cauri-media-library.path_generator'));
        
        // Recréer un objet Media temporaire pour utiliser le PathGenerator
        $tempMedia = new (config('cauri-media-library.media_model'))($this->mediaData);
        
        // Supprimer le fichier original
        $originalPath = $pathGenerator->getPath($tempMedia);
        if (Storage::disk($disk)->exists($originalPath)) {
            Storage::disk($disk)->delete($originalPath);
        }

        // Supprimer les conversions
        $generatedConversions = $this->mediaData['generated_conversions'] ?? [];
        foreach (array_keys($generatedConversions) as $conversionName) {
            $conversionPath = $pathGenerator->getPath($tempMedia, $conversionName);
            if (Storage::disk($disk)->exists($conversionPath)) {
                Storage::disk($disk)->delete($conversionPath);
            }
        }

        // Nettoyer les dossiers vides
        $this->cleanupEmptyDirectories($disk, dirname($originalPath));
    }

    protected function cleanupEmptyDirectories(string $disk, string $directory): void
    {
        try {
            $files = Storage::disk($disk)->files($directory);
            $directories = Storage::disk($disk)->directories($directory);

            if (empty($files) && empty($directories)) {
                Storage::disk($disk)->deleteDirectory($directory);
                
                // Remonter d'un niveau
                $parentDirectory = dirname($directory);
                if ($parentDirectory !== $directory && $parentDirectory !== '.') {
                    $this->cleanupEmptyDirectories($disk, $parentDirectory);
                }
            }
        } catch (\Exception $e) {
            // Ignorer les erreurs de nettoyage
        }
    }

    public function failed(\Exception $exception): void
    {
        \Log::error("DeleteMedia job failed for media {$this->mediaData['id']}: " . $exception->getMessage());
    }
}