<?php

namespace Cauri\MediaLibrary\Conversions\ImageGenerators;

use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use Intervention\Image\Interfaces\ImageInterface;
use Cauri\MediaLibrary\Models\Media;
use Cauri\MediaLibrary\Conversions\Conversion;

abstract class ImageGenerator
{
    protected ImageManager $imageManager;

    public function __construct()
    {
        $driver = config('cauri-media-library.image_driver', 'gd') === 'imagick' 
            ? new ImagickDriver() 
            : new GdDriver();
            
        $this->imageManager = new ImageManager($driver);
    }

    abstract public function canHandle(string $mimeType): bool;
    abstract public function convert(Media $media, Conversion $conversion): string;

    protected function applyResize(ImageInterface $image, Conversion $conversion): ImageInterface
    {
        $width = $conversion->getWidth();
        $height = $conversion->getHeight();

        if ($width === 0 && $height === 0) {
            return $image;
        }

        return match ($conversion->getFit()) {
            'contain' => $this->resizeContain($image, $width, $height),
            'cover' => $this->resizeCover($image, $width, $height),
            'crop' => $this->resizeCrop($image, $width, $height),
            'fill' => $this->resizeFill($image, $width, $height),
            default => $this->resizeContain($image, $width, $height),
        };
    }

    protected function resizeContain(ImageInterface $image, int $width, int $height): ImageInterface
    {
        if ($width > 0 && $height > 0) {
            return $image->scale($width, $height);
        }
        
        if ($width > 0) {
            return $image->scaleDown($width);
        }
        
        if ($height > 0) {
            return $image->scaleDown(height: $height);
        }

        return $image;
    }

    protected function resizeCover(ImageInterface $image, int $width, int $height): ImageInterface
    {
        if ($width > 0 && $height > 0) {
            return $image->cover($width, $height);
        }

        return $this->resizeContain($image, $width, $height);
    }

    protected function resizeCrop(ImageInterface $image, int $width, int $height): ImageInterface
    {
        if ($width > 0 && $height > 0) {
            return $image->crop($width, $height);
        }

        return $this->resizeContain($image, $width, $height);
    }

    protected function resizeFill(ImageInterface $image, int $width, int $height): ImageInterface
    {
        if ($width > 0 && $height > 0) {
            return $image->resize($width, $height);
        }

        return $this->resizeContain($image, $width, $height);
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

    protected function getOutputPath(Media $media, Conversion $conversion): string
    {
        $pathGenerator = app(config('cauri-media-library.path_generator'));
        return $pathGenerator->getPath($media, $conversion->getName());
    }
}