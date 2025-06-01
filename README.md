# CAURI Media Library

[![Latest Version on Packagist](https://img.shields.io/packagist/v/caurilab/media-library.svg?style=flat-square)](https://packagist.org/packages/caurilab/media-library)
[![Total Downloads](https://img.shields.io/packagist/dt/caurilab/media-library.svg?style=flat-square)](https://packagist.org/packages/caurilab/media-library)
[![License](https://img.shields.io/packagist/l/caurilab/media-library.svg?style=flat-square)](https://packagist.org/packages/caurilab/media-library)

A powerful and flexible Laravel package for media management with integrated Vue.js components, optimized for Laravel applications.

## Features

- 🚀 File upload with drag & drop
- 🖼️ Automatic image conversions
- 📱 Responsive images
- 🎨 Ready-to-use Vue.js components
- ⚡ Asynchronous processing with queues
- 🔧 Flexible configuration
- 🛡️ Validation and security
- 📦 Compatible with Laravel 11+ and 12+

## Installation

```bash
composer require caurilab/media-library
```

Publier les fichiers de configuration et migrations :

```bash
php artisan vendor:publish --provider="Cauri\MediaLibrary\CauriMediaLibraryServiceProvider"

php artisan migrate
```
or

```bash
# Publier le fichier de configuration
php artisan vendor:publish --provider="Cauri\MediaLibrary\CauriMediaLibraryServiceProvider" --tag="cauri-media-config"

# Publier les migrations
php artisan vendor:publish --provider="Cauri\MediaLibrary\CauriMediaLibraryServiceProvider" --tag="cauri-media-migrations"

# Publier les composants Vue.js
php artisan vendor:publish --provider="Cauri\MediaLibrary\CauriMediaLibraryServiceProvider" --tag="cauri-media-vue"

# Publier les styles CSS
php artisan vendor:publish --provider="Cauri\MediaLibrary\CauriMediaLibraryServiceProvider" --tag="cauri-media-css"

php artisan migrate
```

## Usage Rapide

```bash
# 1. Installer le package
composer require caurilab/media-library

# 2. Publier la configuration


# 6. Exécuter les migrations
php artisan migrate
```

# 7. Configurer le disk media dans config/filesystems.php
```php
// config/filesystems.php
'disks' => [
    // ... autres disks
    
    'media' => [
        'driver' => 'local',
        'root' => storage_path('app/media'),
        'url' => env('APP_URL').'/storage/media',
        'visibility' => 'public',
        'throw' => false,
    ],
],
```

## Créer le lien symbolique
```bash
php artisan storage:link
```

## Utilisation complète dans un modèle
```php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Cauri\MediaLibrary\Models\Concerns\HasMedia;
use Cauri\MediaLibrary\Models\Media;
use Cauri\MediaLibrary\Conversions\Conversion;

class Product extends Model
{
    use HasMedia;

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('gallery')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
            
        $this->addMediaCollection('documents')
            ->acceptsMimeTypes(['application/pdf'])
            ->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $thumb = new Conversion('thumb');
        $thumb->width(300)->height(300)->quality(80)->format('webp')->fit('cover');
        
        $medium = new Conversion('medium');
        $medium->width(800)->height(600)->quality(85)->format('webp')->fit('contain');
        
        $large = new Conversion('large');
        $large->width(1920)->height(1080)->quality(90)->format('webp')->fit('contain');
    }
}
```
## Utilisation dans les controllers
```php
// Upload
$product = Product::find(1);
$media = $product->addMedia($request->file('image'))
    ->usingName('Photo produit')
    ->withCustomProperties(['alt' => 'Photo du produit'])
    ->toMediaCollection('gallery');

// Récupération
$images = $product->getMedia('gallery');
$firstImageUrl = $product->getFirstMediaUrl('gallery', 'medium');

// Dans les vues Blade
echo $product->getFirstMedia('gallery')?->img('thumb', ['class' => 'img-fluid']);

```

## Commandes Utiles
```bash
# Régénérer toutes les conversions
php artisan cauri-media:regenerate

# Régénérer pour une collection spécifique
php artisan cauri-media:regenerate --collection=gallery

# Nettoyer les fichiers orphelins
php artisan cauri-media:clean

# Dry run pour voir ce qui serait nettoyé
php artisan cauri-media:clean --dry-run

# Nettoyer seulement les conversions obsolètes
php artisan cauri-media:clean --old-conversions
```

## Documentation

Documentation complète disponible sur [docs.caurilab.com](https://docs.caurilab.com/media-library)

## Licence

MIT License. Voir [LICENSE](LICENSE) pour plus de détails.

## Développé par

[CAURI Lab](https://caurilab.com)