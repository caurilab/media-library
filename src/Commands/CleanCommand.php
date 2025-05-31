<?php

namespace Cauri\MediaLibrary\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Cauri\MediaLibrary\Models\Media;

class CleanCommand extends Command
{
    protected $signature = 'cauri-media:clean 
                           {--dry-run : Show what would be deleted without actually deleting}
                           {--force : Delete without confirmation}
                           {--orphaned : Only clean orphaned files}
                           {--old-conversions : Clean old conversion files}';

    protected $description = 'Clean up orphaned media files and old conversions';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        if ($this->option('orphaned')) {
            return $this->cleanOrphanedFiles($dryRun, $force);
        }

        if ($this->option('old-conversions')) {
            return $this->cleanOldConversions($dryRun, $force);
        }

        // Clean both by default
        $this->cleanOrphanedFiles($dryRun, $force);
        $this->cleanOldConversions($dryRun, $force);

        return self::SUCCESS;
    }

    protected function cleanOrphanedFiles(bool $dryRun, bool $force): int
    {
        $this->info('Scanning for orphaned files...');

        $disk = Storage::disk(config('cauri-media-library.disk_name'));
        $allFiles = collect($disk->allFiles());
        $mediaFiles = Media::pluck('file_name')->toArray();

        $orphanedFiles = $allFiles->filter(function ($file) use ($mediaFiles) {
            $fileName = basename($file);
            return !in_array($fileName, $mediaFiles);
        });

        if ($orphanedFiles->isEmpty()) {
            $this->info('No orphaned files found.');
            return self::SUCCESS;
        }

        $this->info("Found {$orphanedFiles->count()} orphaned file(s):");
        
        foreach ($orphanedFiles as $file) {
            $this->line("  - {$file}");
        }

        if ($dryRun) {
            $this->warn('Dry run mode - no files were deleted.');
            return self::SUCCESS;
        }

        if (!$force && !$this->confirm('Do you want to delete these files?')) {
            $this->info('Cleanup cancelled.');
            return self::SUCCESS;
        }

        $deleted = 0;
        foreach ($orphanedFiles as $file) {
            try {
                $disk->delete($file);
                $deleted++;
            } catch (\Exception $e) {
                $this->error("Failed to delete {$file}: {$e->getMessage()}");
            }
        }

        $this->info("Deleted {$deleted} orphaned file(s).");
        return self::SUCCESS;
    }

    protected function cleanOldConversions(bool $dryRun, bool $force): int
    {
        $this->info('Cleaning old conversion files...');

        $mediaItems = Media::whereNotNull('generated_conversions')->get();
        $cleaned = 0;

        foreach ($mediaItems as $media) {
            $conversions = $media->generated_conversions ?? [];
            $pathGenerator = app(config('cauri-media-library.path_generator'));
            $disk = Storage::disk($media->disk);

            foreach (array_keys($conversions) as $conversionName) {
                $conversionPath = $pathGenerator->getPath($media, $conversionName);
                
                // Vérifier si la conversion est toujours configurée
                $defaultConversions = config('cauri-media-library.conversions', []);
                
                if (!array_key_exists($conversionName, $defaultConversions)) {
                    if ($disk->exists($conversionPath)) {
                        $this->line("Found old conversion: {$conversionPath}");
                        
                        if (!$dryRun) {
                            try {
                                $disk->delete($conversionPath);
                                $cleaned++;
                            } catch (\Exception $e) {
                                $this->error("Failed to delete {$conversionPath}: {$e->getMessage()}");
                            }
                        }
                    }
                }
            }
        }

        if ($dryRun) {
            $this->warn('Dry run mode - no files were deleted.');
        } else {
            $this->info("Cleaned {$cleaned} old conversion file(s).");
        }

        return self::SUCCESS;
    }
}