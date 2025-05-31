<?php

namespace Cauri\MediaLibrary\Support;

use Spatie\ImageOptimizer\OptimizerChain;
use Spatie\ImageOptimizer\Optimizers\Jpegoptim;
use Spatie\ImageOptimizer\Optimizers\Pngquant;
use Spatie\ImageOptimizer\Optimizers\Optipng;
use Spatie\ImageOptimizer\Optimizers\Gifsicle;
use Spatie\ImageOptimizer\Optimizers\Cwebp;

class ImageOptimizer
{
    protected OptimizerChain $optimizerChain;

    public function __construct()
    {
        $this->optimizerChain = $this->createOptimizerChain();
    }

    public function optimize(string $pathToImage): void
    {
        if (!file_exists($pathToImage)) {
            throw new \InvalidArgumentException("File does not exist: {$pathToImage}");
        }

        try {
            $this->optimizerChain->optimize($pathToImage);
        } catch (\Exception $e) {
            \Log::warning("Image optimization failed for {$pathToImage}: " . $e->getMessage());
        }
    }

    public function canOptimize(string $mimeType): bool
    {
        $supportedTypes = [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
        ];

        return in_array($mimeType, $supportedTypes);
    }

    protected function createOptimizerChain(): OptimizerChain
    {
        return OptimizerChain::create()
            ->addOptimizer(new Jpegoptim([
                '--max=85',
                '--strip-all',
                '--all-progressive',
            ]))
            ->addOptimizer(new Pngquant([
                '--quality=80-90',
                '--force',
            ]))
            ->addOptimizer(new Optipng([
                '-i0',
                '-o2',
                '-quiet',
            ]))
            ->addOptimizer(new Gifsicle([
                '-b',
                '-O3',
            ]))
            ->addOptimizer(new Cwebp([
                '-q', '85',
                '-m', '6',
            ]));
    }
}