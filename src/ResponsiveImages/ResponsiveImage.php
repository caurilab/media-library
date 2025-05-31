<?php

namespace Cauri\MediaLibrary\ResponsiveImages;

class ResponsiveImage
{
    protected int $width;
    protected int $height;
    protected string $format;
    protected string $url;
    protected string $path;
    protected int $size;

    public function __construct(array $data)
    {
        $this->width = $data['width'];
        $this->height = $data['height'];
        $this->format = $data['format'];
        $this->url = $data['url'];
        $this->path = $data['path'];
        $this->size = $data['size'];
    }

    public function getWidth(): int
    {
        return $this->width;
    }

    public function getHeight(): int
    {
        return $this->height;
    }

    public function getFormat(): string
    {
        return $this->format;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function getSrcSetEntry(): string
    {
        return "{$this->url} {$this->width}w";
    }

    public function toArray(): array
    {
        return [
            'width' => $this->width,
            'height' => $this->height,
            'format' => $this->format,
            'url' => $this->url,
            'path' => $this->path,
            'size' => $this->size,
        ];
    }
}