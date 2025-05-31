# CAURI Media Library - Guide d'Implémentation Pratique

## 🚀 Étape 1 : Initialisation du Repository

### Créer le repo GitHub

```bash
# 1. Créer le dossier local
mkdir caurilab-media-library
cd caurilab-media-library

# 2. Initialiser Git
git init
git remote add origin https://github.com/caurilab/media-library.git

# 3. Créer la structure de base
mkdir -p src/{Models,Collections,Conversions,FileAdder,PathGenerator,UrlGenerator,Http/Controllers/Api,Http/Requests,Jobs,Events,Exceptions,Commands,Support}
mkdir -p config database/migrations resources/{js/components,js/composables,css} routes tests/{Feature,Unit}

# 4. Créer les fichiers essentiels
touch composer.json README.md LICENSE .gitignore
```

### Créer composer.json

```json
{
    "name": "caurilab/media-library",
    "description": "A powerful and flexible media library package for Laravel with Vue.js components",
    "keywords": ["laravel", "media", "upload", "images", "files", "vue", "cauri"],
    "type": "library",
    "license": "MIT",
    "authors": [
        {
            "name": "CAURI Lab",
            "email": "caurilab.dev@gmail.com",
            "homepage": "https://caurilab.dev"
        }
    ],
    "require": {
        "php": "^8.2",
        "laravel/framework": "^11.0|^12.0",
        "intervention/image": "^3.7",
        "spatie/image-optimizer": "^1.7",
        "spatie/temporary-directory": "^2.2",
        "league/flysystem": "^3.0",
        "symfony/mime": "^7.0"
    },
    "require-dev": {
        "orchestra/testbench": "^9.0",
        "phpunit/phpunit": "^11.0",
        "pestphp/pest": "^2.0",
        "pestphp/pest-plugin-laravel": "^2.0"
    },
    "autoload": {
        "psr-4": {
            "Cauri\\MediaLibrary\\": "src/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "Cauri\\MediaLibrary\\Tests\\": "tests/"
        }
    },
    "extra": {
        "laravel": {
            "providers": [
                "Cauri\\MediaLibrary\\CauriMediaLibraryServiceProvider"
            ]
        }
    },
    "minimum-stability": "stable",
    "prefer-stable": true
}
```

## 📁 Étape 2 : Créer les Fichiers Principaux

### Service Provider (src/CauriMediaLibraryServiceProvider.php)

```php
<?php

namespace Cauri\MediaLibrary;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Cauri\MediaLibrary\Commands\RegenerateCommand;
use Cauri\MediaLibrary\Commands\CleanCommand;

class CauriMediaLibraryServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->bootPublishing();
        $this->bootRoutes();
        $this->bootCommands();
    }

    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/cauri-media-library.php',
            'cauri-media-library'
        );

        $this->registerBindings();
    }

    protected function bootPublishing(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/cauri-media-library.php' => config_path('cauri-media-library.php'),
            ], 'cauri-media-config');

            $this->publishes([
                __DIR__.'/../database/migrations/' => database_path('migrations'),
            ], 'cauri-media-migrations');

            $this->publishes([
                __DIR__.'/../resources/js/' => resource_path('js/cauri-media'),
            ], 'cauri-media-vue');
        }
    }

    protected function bootRoutes(): void
    {
        Route::group([
            'prefix' => 'api/cauri-media',
            'middleware' => ['web'],
        ], function () {
            $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
        });
    }

    protected function bootCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                RegenerateCommand::class,
                CleanCommand::class,
            ]);
        }
    }

    protected function registerBindings(): void
    {
        $this->app->bind('cauri-media-library', function () {
            return new MediaLibrary();
        });
    }
}
```

### Configuration (config/cauri-media-library.php)

```php
<?php

return [
    'disk_name' => env('MEDIA_DISK', 'media'),
    'max_file_size' => 1024 * 1024 * 50, // 50MB
    'queue_name' => env('MEDIA_QUEUE', 'default'),
    'queue_conversions_by_default' => true,
    'media_model' => Cauri\MediaLibrary\Models\Media::class,
    'path_generator' => Cauri\MediaLibrary\PathGenerator\DefaultPathGenerator::class,
    'url_generator' => Cauri\MediaLibrary\UrlGenerator\DefaultUrlGenerator::class,
    
    'allowed_mime_types' => [
        'image/jpeg', 'image/png', 'image/gif', 'image/webp',
        'video/mp4', 'application/pdf'
    ],
    
    'conversions' => [
        'thumb' => [
            'width' => 300,
            'height' => 300,
            'quality' => 80,
            'format' => 'webp',
        ],
        'medium' => [
            'width' => 800,
            'height' => 600,
            'quality' => 85,
            'format' => 'webp',
        ],
    ],
];
```

## 🗄️ Étape 3 : Base de Données

### Migration (database/migrations/create_media_table.php)

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->string('collection_name')->default('default');
            $table->string('name');
            $table->string('file_name');
            $table->string('mime_type')->nullable();
            $table->string('disk');
            $table->unsignedBigInteger('size');
            $table->json('custom_properties')->nullable();
            $table->json('generated_conversions')->nullable();
            $table->unsignedInteger('order_column')->nullable();
            $table->uuid('uuid')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['model_type', 'model_id']);
            $table->index('collection_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
```

## 📦 Étape 4 : Modèles de Base

### Modèle Media (src/Models/Media.php)

```php
<?php

namespace Cauri\MediaLibrary\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Media extends Model
{
    use SoftDeletes;

    protected $table = 'media';

    protected $fillable = [
        'model_type', 'model_id', 'collection_name', 'name', 'file_name',
        'mime_type', 'disk', 'size', 'custom_properties', 'generated_conversions',
        'order_column', 'uuid'
    ];

    protected $casts = [
        'custom_properties' => 'array',
        'generated_conversions' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $media) {
            if (empty($media->uuid)) {
                $media->uuid = Str::uuid();
            }
        });
    }

    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    public function getUrl(string $conversionName = ''): string
    {
        $pathGenerator = app(config('cauri-media-library.path_generator'));
        $path = $pathGenerator->getPath($this, $conversionName);
        
        return Storage::disk($this->disk)->url($path);
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mime_type ?? '', 'image/');
    }

    public function getCustomProperty(string $key, $default = null)
    {
        return data_get($this->custom_properties, $key, $default);
    }

    public function setCustomProperty(string $name, $value): self
    {
        $customProperties = $this->custom_properties ?? [];
        $customProperties[$name] = $value;
        $this->custom_properties = $customProperties;
        return $this;
    }
}
```

### Trait HasMedia (src/Models/Concerns/HasMedia.php)

```php
<?php

namespace Cauri\MediaLibrary\Models\Concerns;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Cauri\MediaLibrary\FileAdder\FileAdder;
use Cauri\MediaLibrary\Models\Media;

trait HasMedia
{
    public function media(): MorphMany
    {
        return $this->morphMany(config('cauri-media-library.media_model'), 'model')
            ->orderBy('order_column');
    }

    public function addMedia($file): FileAdder
    {
        return app(FileAdder::class)
            ->setSubject($this)
            ->setFile($file);
    }

    public function getMedia(string $collectionName = 'default')
    {
        return $this->media
            ->filter(fn ($media) => $media->collection_name === $collectionName)
            ->values();
    }

    public function getFirstMedia(string $collectionName = 'default'): ?Media
    {
        return $this->getMedia($collectionName)->first();
    }

    public function getFirstMediaUrl(string $collectionName = 'default', string $conversion = ''): string
    {
        $media = $this->getFirstMedia($collectionName);
        return $media ? $media->getUrl($conversion) : '';
    }
}
```

## 🔧 Étape 5 : Première Implémentation

### FileAdder Simplifié (src/FileAdder/FileAdder.php)

```php
<?php

namespace Cauri\MediaLibrary\FileAdder;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Cauri\MediaLibrary\Models\Media;

class FileAdder
{
    protected Model $subject;
    protected $file;
    protected string $collectionName = 'default';
    protected string $name = '';
    protected string $fileName = '';
    protected string $disk;
    protected array $customProperties = [];

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
            $this->fileName = $file->getClientOriginalName();
        }
        
        return $this;
    }

    public function usingName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function withCustomProperties(array $customProperties): self
    {
        $this->customProperties = $customProperties;
        return $this;
    }

    public function toMediaCollection(string $collectionName = 'default'): Media
    {
        $this->collectionName = $collectionName;
        return $this->save();
    }

    protected function save(): Media
    {
        $mediaClass = config('cauri-media-library.media_model');
        
        $media = new $mediaClass([
            'model_type' => get_class($this->subject),
            'model_id' => $this->subject->getKey(),
            'collection_name' => $this->collectionName,
            'name' => $this->name,
            'file_name' => $this->fileName,
            'mime_type' => $this->file->getMimeType(),
            'disk' => $this->disk,
            'size' => $this->file->getSize(),
            'custom_properties' => $this->customProperties,
        ]);

        $media->save();
        $this->storeFile($media);
        
        return $media;
    }

    protected function storeFile(Media $media): void
    {
        $pathGenerator = app(config('cauri-media-library.path_generator'));
        $path = $pathGenerator->getPath($media);
        
        Storage::disk($this->disk)->putFileAs(
            dirname($path),
            $this->file,
            basename($path)
        );
    }
}
```

### PathGenerator (src/PathGenerator/DefaultPathGenerator.php)

```php
<?php

namespace Cauri\MediaLibrary\PathGenerator;

use Cauri\MediaLibrary\Models\Media;

class DefaultPathGenerator
{
    public function getPath(Media $media, string $conversionName = ''): string
    {
        $basePath = implode('/', [
            class_basename($media->model_type),
            $media->model_id,
            $media->collection_name,
            $media->id,
        ]);
        
        if ($conversionName === '') {
            return $basePath . '/' . $media->file_name;
        }

        $pathInfo = pathinfo($media->file_name);
        $conversionFileName = $pathInfo['filename'] . '-' . $conversionName . '.webp';
        
        return $basePath . '/conversions/' . $conversionFileName;
    }
}
```

## 🎮 Étape 6 : API Controller

### routes/api.php

```php
<?php

use Illuminate\Support\Facades\Route;
use Cauri\MediaLibrary\Http\Controllers\Api\MediaApiController;

Route::post('/upload', [MediaApiController::class, 'upload']);
Route::delete('/{media}', [MediaApiController::class, 'destroy']);
```

### Controller API (src/Http/Controllers/Api/MediaApiController.php)

```php
<?php

namespace Cauri\MediaLibrary\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Cauri\MediaLibrary\Models\Media;

class MediaApiController extends Controller
{
    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'files' => 'required|array',
            'files.*' => 'required|file|max:' . (config('cauri-media-library.max_file_size') / 1024),
            'model_type' => 'sometimes|string',
            'model_id' => 'sometimes|integer',
            'collection' => 'sometimes|string',
        ]);

        try {
            $uploadedMedia = collect();

            foreach ($request->file('files') as $file) {
                if ($request->filled(['model_type', 'model_id'])) {
                    $modelClass = $request->input('model_type');
                    $model = $modelClass::findOrFail($request->input('model_id'));
                    
                    $media = $model->addMedia($file)
                        ->toMediaCollection($request->input('collection', 'default'));
                } else {
                    // Handle standalone media if needed
                    throw new \Exception('Model type and ID required');
                }

                $uploadedMedia->push([
                    'id' => $media->id,
                    'name' => $media->name,
                    'url' => $media->getUrl(),
                    'thumb_url' => $media->getUrl('thumb'),
                    'size' => $media->size,
                    'mime_type' => $media->mime_type,
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => ['media' => $uploadedMedia],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function destroy(Media $media): JsonResponse
    {
        try {
            $media->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Media deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete media',
            ], 500);
        }
    }
}
```

## 🧪 Étape 7 : Test d'Installation

### Créer un projet test Laravel

```bash
# 1. Nouveau projet Laravel
composer create-project laravel/laravel cauri-media-test
cd cauri-media-test

# 2. Ajouter le package en développement local
# Dans composer.json, ajouter :
{
    "repositories": [
        {
            "type": "path",
            "url": "../caurilab-media-library"
        }
    ],
    "require": {
        "caurilab/media-library": "@dev"
    }
}

# 3. Installer
composer install

# 4. Configuration
php artisan vendor:publish --provider="Cauri\MediaLibrary\CauriMediaLibraryServiceProvider"
```

### Configurer le disk media (config/filesystems.php)

```php
'disks' => [
    // ... autres disks
    
    'media' => [
        'driver' => 'local',
        'root' => storage_path('app/media'),
        'url' => env('APP_URL').'/storage/media',
        'visibility' => 'public',
    ],
],
```

### Créer le lien et migrer

```bash
php artisan storage:link
php artisan migrate
```

### Modèle de test (app/Models/Product.php)

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Cauri\MediaLibrary\Models\Concerns\HasMedia;

class Product extends Model
{
    use HasMedia;

    protected $fillable = ['name', 'description'];

    public function registerMediaCollections(): void
    {
        // Méthode requise par le trait
    }

    public function registerMediaConversions($media = null): void
    {
        // Méthode requise par le trait
    }
}
```

### Route de test (routes/web.php)

```php
<?php

use Illuminate\Support\Facades\Route;
use App\Models\Product;

Route::get('/test-upload', function () {
    return view('test-upload');
});

Route::post('/test-upload', function () {
    $product = Product::firstOrCreate(['name' => 'Test Product']);
    
    if (request()->hasFile('file')) {
        $media = $product->addMedia(request()->file('file'))
            ->usingName('Test Image')
            ->toMediaCollection('gallery');
            
        return response()->json([
            'success' => true,
            'media' => [
                'id' => $media->id,
                'url' => $media->getUrl(),
                'name' => $media->name,
            ]
        ]);
    }
    
    return response()->json(['error' => 'No file uploaded'], 400);
});
```

### Vue de test (resources/views/test-upload.blade.php)

```html
<!DOCTYPE html>
<html>
<head>
    <title>Test CAURI Media Library</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body>
    <h1>Test Upload</h1>
    
    <form id="uploadForm" enctype="multipart/form-data">
        <input type="file" name="file" accept="image/*" required>
        <button type="submit">Upload</button>
    </form>
    
    <div id="result"></div>

    <script>
        document.getElementById('uploadForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const formData = new FormData(e.target);
            
            try {
                const response = await fetch('/test-upload', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('[name="csrf-token"]').content
                    },
                    body: formData
                });
                
                const result = await response.json();
                document.getElementById('result').innerHTML = 
                    '<pre>' + JSON.stringify(result, null, 2) + '</pre>';
                    
                if (result.success) {
                    document.getElementById('result').innerHTML += 
                        '<img src="' + result.media.url + '" style="max-width: 300px;">';
                }
            } catch (error) {
                console.error('Error:', error);
            }
        });
    </script>
</body>
</html>
```

## 🏁 Étape 8 : Test Final

```bash
# 1. Lancer le serveur
php artisan serve

# 2. Aller sur http://localhost:8000/test-upload

# 3. Uploader une image

# 4. Vérifier que :
#    - L'image est uploadée
#    - Le fichier est dans storage/app/media/
#    - L'URL fonctionne
#    - Les données sont en base
```

## 🔄 Prochaines Étapes

1. **Compléter les fonctionnalités manquantes** une par une
2. **Ajouter les conversions d'images**
3. **Implémenter les composants Vue.js**
4. **Ajouter les tests**
5. **Publier sur Packagist**

Veux-tu qu'on commence par tester cette implémentation de base ou qu'on ajoute directement une fonctionnalité spécifique ? 🚀