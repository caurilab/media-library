<?php

namespace Cauri\MediaLibrary\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Cauri\MediaLibrary\PathGenerator\PathGenerator;
use Cauri\MediaLibrary\UrlGenerator\UrlGenerator;
use Cauri\MediaLibrary\Events\MediaHasBeenAdded;
use Cauri\MediaLibrary\Events\MediaHasBeenDeleted;

class Media extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'media';

    protected $fillable = [
        'model_type',
        'model_id',
        'collection_name',
        'name',
        'file_name',
        'mime_type',
        'disk',
        'size',
        'custom_properties',
        'generated_conversions',
        'responsive_images',
        'order_column',
        'sha1_hash',
        'uuid',
    ];

    protected $casts = [
        'custom_properties' => 'array',
        'generated_conversions' => 'array',
        'responsive_images' => 'array',
        'size' => 'integer',
        'order_column' => 'integer',
    ];

    protected $hidden = [
        'sha1_hash',
    ];

    protected $appends = [
        'human_readable_size',
        'extension',
        'type',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $media) {
            if (empty($media->uuid)) {
                $media->uuid = Str::uuid();
            }
        });

        static::created(function (self $media) {
            event(new MediaHasBeenAdded($media));
        });

        static::deleted(function (self $media) {
            event(new MediaHasBeenDeleted($media));
        });
    }

    // Relations
    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    // Accessors
    public function getHumanReadableSizeAttribute(): string
    {
        return $this->humanFileSize($this->size);
    }

    public function getExtensionAttribute(): string
    {
        return pathinfo($this->file_name, PATHINFO_EXTENSION);
    }

    public function getTypeAttribute(): string
    {
        if ($this->isImage()) return 'image';
        if ($this->isVideo()) return 'video';
        if ($this->isAudio()) return 'audio';
        if ($this->isPdf()) return 'pdf';
        if ($this->isArchive()) return 'archive';
        
        return 'document';
    }

    // URL et Path Methods
    public function getUrl(string $conversionName = ''): string
    {
        $urlGenerator = app(config('cauri-media-library.url_generator'));
        
        return $urlGenerator->getUrl($this, $conversionName);
    }

    public function getTemporaryUrl(\DateTimeInterface $expiration, string $conversionName = ''): string
    {
        $urlGenerator = app(config('cauri-media-library.url_generator'));
        
        if (method_exists($urlGenerator, 'getTemporaryUrl')) {
            return $urlGenerator->getTemporaryUrl($this, $expiration, $conversionName);
        }
        
        return $this->getUrl($conversionName);
    }

    public function getPath(string $conversionName = ''): string
    {
        $pathGenerator = app(config('cauri-media-library.path_generator'));
        
        return $pathGenerator->getPath($this, $conversionName);
    }

    public function getFullPath(string $conversionName = ''): string
    {
        return Storage::disk($this->disk)->path($this->getPath($conversionName));
    }

    // Custom Properties
    public function getCustomProperty(string $propertyName, $default = null)
    {
        return data_get($this->custom_properties, $propertyName, $default);
    }

    public function setCustomProperty(string $name, $value): self
    {
        $customProperties = $this->custom_properties ?? [];
        $customProperties[$name] = $value;
        $this->custom_properties = $customProperties;

        return $this;
    }

    public function forgetCustomProperty(string $name): self
    {
        $customProperties = $this->custom_properties ?? [];
        unset($customProperties[$name]);
        $this->custom_properties = $customProperties;

        return $this;
    }

    public function hasCustomProperty(string $propertyName): bool
    {
        return array_key_exists($propertyName, $this->custom_properties ?? []);
    }

    // Conversions
    public function hasGeneratedConversion(string $conversionName): bool
    {
        return array_key_exists($conversionName, $this->generated_conversions ?? []);
    }

    public function markAsConversionGenerated(string $conversionName, bool $generated = true): self
    {
        $generatedConversions = $this->generated_conversions ?? [];
        $generatedConversions[$conversionName] = $generated;
        $this->generated_conversions = $generatedConversions;

        return $this;
    }

    public function markAsConversionFailed(string $conversionName): self
    {
        return $this->markAsConversionGenerated($conversionName, false);
    }

    // File Type Checks
    public function isImage(): bool
    {
        return str_starts_with($this->mime_type ?? '', 'image/');
    }

    public function isVideo(): bool
    {
        return str_starts_with($this->mime_type ?? '', 'video/');
    }

    public function isAudio(): bool
    {
        return str_starts_with($this->mime_type ?? '', 'audio/');
    }

    public function isPdf(): bool
    {
        return $this->mime_type === 'application/pdf';
    }

    public function isArchive(): bool
    {
        $archiveMimes = [
            'application/zip',
            'application/x-rar-compressed',
            'application/x-7z-compressed',
            'application/x-tar',
            'application/gzip',
        ];

        return in_array($this->mime_type, $archiveMimes);
    }

    // File Operations
    public function copy(?Model $model = null, string $collectionName = 'default'): self
    {
        $newMedia = $this->replicate();
        
        if ($model) {
            $newMedia->model()->associate($model);
        }
        
        $newMedia->collection_name = $collectionName;
        $newMedia->uuid = Str::uuid();
        $newMedia->save();

        // Copy file
        $originalPath = $this->getPath();
        $newPath = $newMedia->getPath();
        
        Storage::disk($this->disk)->copy($originalPath, $newPath);

        // Copy conversions
        foreach (array_keys($this->generated_conversions ?? []) as $conversionName) {
            $originalConversionPath = $this->getPath($conversionName);
            $newConversionPath = $newMedia->getPath($conversionName);
            
            if (Storage::disk($this->disk)->exists($originalConversionPath)) {
                Storage::disk($this->disk)->copy($originalConversionPath, $newConversionPath);
            }
        }

        return $newMedia;
    }

    public function move(?Model $model = null, string $collectionName = 'default'): self
    {
        $originalModel = $this->model;
        $originalCollection = $this->collection_name;

        if ($model) {
            $this->model()->associate($model);
        }
        
        $this->collection_name = $collectionName;
        $this->save();

        return $this;
    }

    public function delete(): bool
    {
        $this->deleteFile();
        $this->deleteConversions();

        return parent::delete();
    }

    public function forceDelete(): bool
    {
        $this->deleteFile();
        $this->deleteConversions();

        return parent::forceDelete();
    }

    protected function deleteFile(): void
    {
        $path = $this->getPath();
        
        if (Storage::disk($this->disk)->exists($path)) {
            Storage::disk($this->disk)->delete($path);
        }
    }

    protected function deleteConversions(): void
    {
        $generatedConversions = $this->generated_conversions ?? [];
        
        foreach (array_keys($generatedConversions) as $conversionName) {
            $conversionPath = $this->getPath($conversionName);
            
            if (Storage::disk($this->disk)->exists($conversionPath)) {
                Storage::disk($this->disk)->delete($conversionPath);
            }
        }
    }

    // HTML Output
    public function img(string $conversion = '', array $extraAttributes = []): string
    {
        if (!$this->isImage()) {
            return '';
        }

        $url = $this->getUrl($conversion);
        $alt = $extraAttributes['alt'] ?? $this->name;
        
        $attributes = array_merge([
            'src' => $url,
            'alt' => $alt,
            'loading' => 'lazy',
        ], $extraAttributes);

        return sprintf(
            '<img %s>',
            collect($attributes)
                ->map(fn ($value, $key) => sprintf('%s="%s"', $key, htmlspecialchars($value)))
                ->implode(' ')
        );
    }

    public function toHtml(string $conversion = '', array $extraAttributes = []): string
    {
        if ($this->isImage()) {
            return $this->img($conversion, $extraAttributes);
        }

        $url = $this->getUrl($conversion);
        $name = $extraAttributes['title'] ?? $this->name;

        return sprintf(
            '<a href="%s" target="_blank" rel="noopener">%s</a>',
            htmlspecialchars($url),
            htmlspecialchars($name)
        );
    }

    // Utilities
    protected function humanFileSize(int $bytes, int $decimals = 2): string
    {
        $size = ['B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
        $factor = floor((strlen($bytes) - 1) / 3);

        return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . ' ' . @$size[$factor];
    }

    // Scopes
    public function scopeImages($query)
    {
        return $query->where('mime_type', 'like', 'image/%');
    }

    public function scopeVideos($query)
    {
        return $query->where('mime_type', 'like', 'video/%');
    }

    public function scopeInCollection($query, string $collectionName)
    {
        return $query->where('collection_name', $collectionName);
    }

    public function scopeOrdered($query, string $direction = 'asc')
    {
        return $query->orderBy('order_column', $direction);
    }
}