<?php

namespace Cauri\MediaLibrary\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Cauri\MediaLibrary\Models\Media;
use Cauri\MediaLibrary\Conversions\ConversionCollection;
use Cauri\MediaLibrary\Conversions\ImageGenerators\Image;
use Cauri\MediaLibrary\Events\ConversionHasBeenCompleted;

class GenerateConversions implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300; // 5 minutes
    public int $tries = 3;

    protected Media $media;

    public function __construct(Media $media)
    {
        $this->media = $media;
        $this->onQueue(config('cauri-media-library.queue_name', 'default'));
    }

    public function handle(): void
    {
        if (!$this->media->exists) {
            return;
        }

        // Charger les conversions définies pour ce média
        $conversions = $this->getMediaConversions();

        if ($conversions->isEmpty()) {
            return;
        }

        // Générer chaque conversion
        foreach ($conversions as $conversion) {
            try {
                $this->generateConversion($conversion);
                
                // Marquer comme généré
                $this->media->markAsConversionGenerated($conversion->getName(), true);
                $this->media->save();

                event(new ConversionHasBeenCompleted($this->media, $conversion));

            } catch (\Exception $e) {
                \Log::error("Conversion failed for media {$this->media->id}, conversion {$conversion->getName()}: " . $e->getMessage());
                
                // Marquer comme échoué
                $this->media->markAsConversionGenerated($conversion->getName(), false);
                $this->media->save();
            }
        }
    }

    protected function getMediaConversions(): ConversionCollection
    {
        $model = $this->media->model;
        
        if (!$model || !method_exists($model, 'registerMediaConversions')) {
            return new ConversionCollection();
        }

        // Simuler l'enregistrement des conversions
        $conversions = new ConversionCollection();
        
        // Obtenir les conversions par défaut de la configuration
        $defaultConversions = config('cauri-media-library.conversions', []);
        
        foreach ($defaultConversions as $name => $settings) {
            $conversion = new \Cauri\MediaLibrary\Conversions\Conversion($name);
            
            if (isset($settings['width'])) {
                $conversion->width($settings['width']);
            }
            
            if (isset($settings['height'])) {
                $conversion->height($settings['height']);
            }
            
            if (isset($settings['quality'])) {
                $conversion->quality($settings['quality']);
            }
            
            if (isset($settings['format'])) {
                $conversion->format($settings['format']);
            }
            
            if (isset($settings['fit'])) {
                $conversion->fit($settings['fit']);
            }

            $conversions->push($conversion);
        }

        // Permettre au modèle d'ajouter ses propres conversions
        if (method_exists($model, 'registerMediaConversions')) {
            $model->registerMediaConversions($this->media);
        }

        return $conversions;
    }

    protected function generateConversion($conversion): void
    {
        $generator = $this->getImageGenerator();
        
        if (!$generator->canHandle($this->media->mime_type)) {
            throw new \Exception("No generator available for mime type: {$this->media->mime_type}");
        }

        $generator->convert($this->media, $conversion);
    }

    protected function getImageGenerator()
    {
        return new Image();
    }

    public function failed(\Exception $exception): void
    {
        \Log::error("GenerateConversions job failed for media {$this->media->id}: " . $exception->getMessage());
    }
}