<?php

namespace Cauri\MediaLibrary\Commands;

use Illuminate\Console\Command;
use Cauri\MediaLibrary\Models\Media;
use Cauri\MediaLibrary\Jobs\GenerateConversions;

class RegenerateCommand extends Command
{
    protected $signature = 'cauri-media:regenerate 
                           {--ids=* : Specific media IDs to regenerate}
                           {--collection= : Regenerate only this collection}
                           {--model= : Regenerate for specific model type}
                           {--force : Force regeneration even if conversions exist}
                           {--queue : Queue the regeneration jobs}';

    protected $description = 'Regenerate media conversions';

    public function handle(): int
    {
        $mediaClass = config('cauri-media-library.media_model');
        
        $query = $mediaClass::query();

        // Filter by specific IDs
        if ($this->option('ids')) {
            $query->whereIn('id', $this->option('ids'));
        }

        // Filter by collection
        if ($this->option('collection')) {
            $query->where('collection_name', $this->option('collection'));
        }

        // Filter by model type
        if ($this->option('model')) {
            $query->where('model_type', $this->option('model'));
        }

        $mediaItems = $query->get();

        if ($mediaItems->isEmpty()) {
            $this->info('No media items found matching the criteria.');
            return self::SUCCESS;
        }

        $this->info("Found {$mediaItems->count()} media item(s) to process.");

        $progressBar = $this->output->createProgressBar($mediaItems->count());
        $progressBar->start();

        $processed = 0;
        $failed = 0;

        foreach ($mediaItems as $media) {
            try {
                if ($this->option('queue')) {
                    GenerateConversions::dispatch($media);
                    $this->line(" Queued: {$media->name}");
                } else {
                    $job = new GenerateConversions($media);
                    $job->handle();
                    $this->line(" Processed: {$media->name}");
                }
                
                $processed++;
            } catch (\Exception $e) {
                $this->error(" Failed: {$media->name} - {$e->getMessage()}");
                $failed++;
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->info("Regeneration completed!");
        $this->info("Processed: {$processed}");
        
        if ($failed > 0) {
            $this->warn("Failed: {$failed}");
        }

        return self::SUCCESS;
    }
}