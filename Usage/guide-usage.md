```bash
# 1. Installer le package
composer require caurilab/media-library

# 2. Publier la configuration
php artisan vendor:publish --provider="Cauri\MediaLibrary\CauriMediaLibraryServiceProvider" --tag="cauri-media-config"

# 3. Publier les migrations
php artisan vendor:publish --provider="Cauri\MediaLibrary\CauriMediaLibraryServiceProvider" --tag="cauri-media-migrations"

# 4. Publier les composants Vue.js
php artisan vendor:publish --provider="Cauri\MediaLibrary\CauriMediaLibraryServiceProvider" --tag="cauri-media-vue"

# 5. Publier les styles CSS
php artisan vendor:publish --provider="Cauri\MediaLibrary\CauriMediaLibraryServiceProvider" --tag="cauri-media-css"

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
php artisan storage:link

## Utilisation complète dans un modèle
```php
<?php

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
✅ Checklist Finale

✅ Phase 1 : Foundation (composer.json, Service Provider, configuration)
✅ Phase 2 : Base de données (migration, modèles)
✅ Phase 3 : Logique métier (FileAdder, PathGenerator, UrlGenerator)
✅ Phase 4 : API et Controllers
✅ Phase 5 : Composants Vue.js
✅ Phase 6 : Conversions, Jobs et optimisations

Le package CAURI Media Library est maintenant complet et prêt pour production ! 🚀
Points forts :

Architecture modulaire et extensible
API REST complète pour le frontend
Composants Vue.js modernes avec Composition API
Système de conversions automatiques avec optimisation
Jobs en queue pour les gros traitements
Images responsives et placeholders
Commandes Artisan pour la maintenance
Tests complets et documentation