# CAURI Media Library

[![Latest Version on Packagist](https://img.shields.io/packagist/v/caurilab/media-library.svg?style=flat-square)](https://packagist.org/packages/caurilab/media-library)
[![Total Downloads](https://img.shields.io/packagist/dt/caurilab/media-library.svg?style=flat-square)](https://packagist.org/packages/caurilab/media-library)
[![License](https://img.shields.io/packagist/l/caurilab/media-library.svg?style=flat-square)](https://packagist.org/packages/caurilab/media-library)

Un package Laravel puissant et flexible pour la gestion de médias avec des composants Vue.js intégrés, optimisé pour les applications web modernes.

## Fonctionnalités

- 🚀 Upload de fichiers avec drag & drop
- 🖼️ Conversions d'images automatiques
- 📱 Images responsives
- 🎨 Composants Vue.js prêts à l'emploi
- ⚡ Traitement asynchrone avec les queues
- 🔧 Configuration flexible
- 🛡️ Validation et sécurité renforcées
- 📦 Compatible Laravel 11+ et 12+

## Installation

```bash
composer require caurilab/media-library
```

Publier les fichiers de configuration et migrations :

```bash
php artisan vendor:publish --provider="Cauri\MediaLibrary\CauriMediaLibraryServiceProvider"
php artisan migrate
```

## Usage Rapide

```php
use Cauri\MediaLibrary\Models\Concerns\HasMedia;

class Product extends Model
{
    use HasMedia;
    
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('images')
            ->acceptsMimeTypes(['image/jpeg', 'image/png']);
    }
}

// Ajouter un média
$product = Product::find(1);
$product->addMedia($file)->toMediaCollection('images');

// Récupérer l'URL
$imageUrl = $product->getFirstMediaUrl('images', 'thumb');
```

## Documentation

Documentation complète disponible sur [docs.caurilab.com](https://docs.caurilab.com/media-library)

## Licence

MIT License. Voir [LICENSE](LICENSE) pour plus de détails.

## Développé par

[CAURI Lab](https://caurilab.com)