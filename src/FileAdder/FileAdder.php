<?php

namespace Cauri\MediaLibrary\FileAdder;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Cauri\MediaLibrary\Models\Media;
use Cauri\MediaLibrary\Jobs\GenerateConversions;
use Cauri\MediaLibrary\Exceptions\FileCannotBeAdded;
use Cauri\MediaLibrary\Support\FileTypes;
use Spatie\TemporaryDirectory\TemporaryDirectory;

class FileAdder
{
    protected Model $subject;
    protected $file;
    protected string $collectionName = 'default';
    protected string $name = '';
    protected string $fileName = '';
    protected string $disk;
    protected array $customProperties = [];
    protected bool $preserveOriginal = true;
    protected bool $generateResponsiveImages = false;
    protected ?TemporaryDirectory $temporaryDirectory = null;

    public function __construct()
    {
        $this->disk = config('cauri-media-library.disk_name');
    }

    public function setSubject(Model $subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    public function setFile($file): self
    {
        $this->file = $file;

        if ($file instanceof UploadedFile) {
            $this->name = $file->getClientOriginalName();
            $this->fileName = $this->sanitizeFileName($file->getClientOriginalName());
        } elseif (is_string($file) && filter_var($file, FILTER_VALIDATE_URL)) {
            $this->handleUrl($file);
        } elseif (is_string($file)) {
            // String content
            $this->name = 'string-content';
            $this->fileName = 'string-content.txt';
        }

        return $this;
    }

    public function usingName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function usingFileName(string $fileName): self
    {
        $this->fileName = $this->sanitizeFileName($fileName);

        return $this;
    }

    public function toMediaCollection(string $collectionName = 'default'): Media
    {
        $this->collectionName = $collectionName;

        return $this->save();
    }

    public function withCustomProperties(array $customProperties): self
    {
        $this->customProperties = $customProperties;

        return $this;
    }

    public function addCustomProperty(string $key, $value): self
    {
        $this->customProperties[$key] = $value;

        return $this;
    }

    public function preserveOriginal(bool $preserveOriginal = true): self
    {
        $this->preserveOriginal = $preserveOriginal;

        return $this;
    }

    public function generateResponsiveImages(bool $generateResponsiveImages = true): self
    {
        $this->generateResponsiveImages = $generateResponsiveImages;

        return $this;
    }

    public function storingOn(string $disk): self
    {
        $this->disk = $disk;

        return $this;
    }

    protected function save(): Media
    {
        $this->validateFile();
        $this->ensureFileExists();

        $media = $this->createMediaRecord();
        $this->storeFile($media);
        $this->processConversions($media);

        return $media;
    }

    protected function validateFile(): void
    {
        $validator = Validator::make([
            'file' => $this->file,
        ], [
            'file' => [
                'required',
                'file',
                'max:' . (config('cauri-media-library.max_file_size') / 1024),
                function ($attribute, $value, $fail) {
                    if (!$this->isAllowedMimeType($this->getMimeType())) {
                        $fail('The file type is not allowed.');
                    }
                },
            ],
        ]);

        if ($validator->fails()) {
            throw new FileCannotBeAdded($validator->errors()->first());
        }
    }

    protected function ensureFileExists(): void
    {
        if ($this->file instanceof UploadedFile) {
            if (!$this->file->isValid()) {
                throw new FileCannotBeAdded('The uploaded file is not valid.');
            }
            return;
        }

        if (is_string($this->file) && filter_var($this->file, FILTER_VALIDATE_URL)) {
            $this->downloadFromUrl();
            return;
        }

        if (is_string($this->file)) {
            $this->saveStringToTemporaryFile();
            return;
        }

        throw new FileCannotBeAdded('Invalid file type provided.');
    }

    protected function createMediaRecord(): Media
    {
        $mediaClass = config('cauri-media-library.media_model');

        $media = new $mediaClass([
            'model_type' => get_class($this->subject),
            'model_id' => $this->subject->getKey(),
            'collection_name' => $this->collectionName,
            'name' => $this->name,
            'file_name' => $this->fileName,
            'mime_type' => $this->getMimeType(),
            'disk' => $this->disk,
            'size' => $this->getFileSize(),
            'custom_properties' => $this->customProperties,
            'sha1_hash' => $this->generateHash(),
            'uuid' => Str::uuid(),
        ]);

        $media->save();

        return $media;
    }

    protected function storeFile(Media $media): void
    {
        $pathGenerator = app(config('cauri-media-library.path_generator'));
        $path = $pathGenerator->getPath($media);
        $directory = dirname($path);

        // Ensure directory exists
        if (!Storage::disk($this->disk)->exists($directory)) {
            Storage::disk($this->disk)->makeDirectory($directory);
        }

        if ($this->file instanceof UploadedFile) {
            Storage::disk($this->disk)->putFileAs(
                $directory,
                $this->file,
                basename($path)
            );
        } else {
            Storage::disk($this->disk)->put($path, $this->getFileContent());
        }
    }

    protected function processConversions(Media $media): void
    {
        if (config('cauri-media-library.queue_conversions_by_default')) {
            GenerateConversions::dispatch($media);
        } else {
            // Process synchronously for immediate results
            app(GenerateConversions::class)->handle($media);
        }
    }

    protected function handleUrl(string $url): void
    {
        $parsedUrl = parse_url($url);
        $pathInfo = pathinfo($parsedUrl['path'] ?? '');
        
        $this->name = $pathInfo['basename'] ?? 'downloaded-file';
        $this->fileName = $this->sanitizeFileName($this->name);
    }

    protected function downloadFromUrl(): void
    {
        if (!filter_var($this->file, FILTER_VALIDATE_URL)) {
            throw new FileCannotBeAdded('Invalid URL provided.');
        }

        $temporaryDirectory = $this->getTemporaryDirectory();
        $tempFile = $temporaryDirectory->path($this->fileName);

        $content = file_get_contents($this->file);
        
        if ($content === false) {
            throw new FileCannotBeAdded('Could not download file from URL.');
        }

        file_put_contents($tempFile, $content);
        $this->file = $tempFile;
    }

    protected function saveStringToTemporaryFile(): void
    {
        $temporaryDirectory = $this->getTemporaryDirectory();
        $tempFile = $temporaryDirectory->path($this->fileName);

        file_put_contents($tempFile, $this->file);
        $this->file = $tempFile;
    }

    protected function getTemporaryDirectory(): TemporaryDirectory
    {
        if (!$this->temporaryDirectory) {
            $this->temporaryDirectory = TemporaryDirectory::make()
                ->location(config('cauri-media-library.temporary_directory_path'))
                ->create();
        }

        return $this->temporaryDirectory;
    }

    protected function getMimeType(): string
    {
        if ($this->file instanceof UploadedFile) {
            return $this->file->getMimeType() ?? 'application/octet-stream';
        }

        if (is_string($this->file) && file_exists($this->file)) {
            return mime_content_type($this->file) ?: 'application/octet-stream';
        }

        return 'application/octet-stream';
    }

    protected function getFileSize(): int
    {
        if ($this->file instanceof UploadedFile) {
            return $this->file->getSize() ?? 0;
        }

        if (is_string($this->file) && file_exists($this->file)) {
            return filesize($this->file) ?: 0;
        }

        if (is_string($this->file)) {
            return strlen($this->file);
        }

        return 0;
    }

    protected function getFileContent(): string
    {
        if ($this->file instanceof UploadedFile) {
            return $this->file->getContent();
        }

        if (is_string($this->file) && file_exists($this->file)) {
            return file_get_contents($this->file);
        }

        return (string) $this->file;
    }

    protected function generateHash(): string
    {
        return sha1($this->getFileContent());
    }

    protected function sanitizeFileName(string $fileName): string
    {
        if (!config('cauri-media-library.sanitize_filenames', true)) {
            return $fileName;
        }

        $pathInfo = pathinfo($fileName);
        $name = $pathInfo['filename'] ?? '';
        $extension = $pathInfo['extension'] ?? '';

        // Remove special characters and spaces
        $name = preg_replace('/[^a-zA-Z0-9\-_]/', '_', $name);
        $name = preg_replace('/_+/', '_', $name);
        $name = trim($name, '_');

        if (empty($name)) {
            $name = 'file_' . time();
        }

        return $name . ($extension ? '.' . $extension : '');
    }

    protected function isAllowedMimeType(string $mimeType): bool
    {
        $allowedMimeTypes = config('cauri-media-library.allowed_mime_types', []);
        
        if (empty($allowedMimeTypes)) {
            return true; // Allow all if not configured
        }

        return in_array($mimeType, $allowedMimeTypes);
    }

    public function __destruct()
    {
        if ($this->temporaryDirectory) {
            $this->temporaryDirectory->delete();
        }
    }
}