```php
<?php

// ===== EXEMPLES D'UTILISATION DU TRAIT INTÉGRÉ =====

// 1. Dans un Controller avec le trait
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Cauri\MediaLibrary\Traits\FileUploadTrait;
use App\Models\Product;

class ProductController extends Controller
{
    use FileUploadTrait;
    
    public function store(Request $request)
    {
        $product = Product::create($request->validated());
        
        // ✅ MÉTHODE 1: Upload simple avec conversions automatiques
        if ($request->hasFile('image')) {
            $this->saveFileWithConversions(
                $request->file('image'),
                'Image produit ' . $product->name,
                $product,
                'gallery'
            );
        }
        
        // ✅ MÉTHODE 2: Upload multiple avec traitement automatique
        $savedMedia = $this->saveRequestFiles($request, $product, ['gallery', 'documents']);
        
        // ✅ MÉTHODE 3: Upload avec tailles personnalisées (comme ton code original)
        if ($request->hasFile('featured_image')) {
            $customSizes = [
                ['width' => 150, 'height' => 150, 'name' => 'thumb'],
                ['width' => 600, 'height' => 400, 'name' => 'card'],
                ['width' => 1200, 'height' => 800, 'name' => 'hero'],
            ];
            
            $this->saveFileWithCustomSizes(
                $request->file('featured_image'),
                'Image vedette',
                $customSizes,
                $product
            );
        }
        
        return redirect()->route('products.show', $product);
    }
    
    public function uploadLogos(Request $request)
    {
        // ✅ MÉTHODE 4: Upload de logos (adaptation de ta méthode)
        $logos = $this->saveLogos($request);
        
        return response()->json(['logos' => $logos]);
    }
}

// 2. Utilisation avec les macros Request
class AnotherController extends Controller
{
    public function store(Request $request)
    {
        $model = SomeModel::create($request->validated());
        
        // ✅ MACRO: Upload automatique via Request
        $savedMedia = $request->saveMedia($model, ['images', 'documents']);
        
        // ✅ MACRO: Upload de logos
        $logos = $request->saveLogos($model);
        
        // ✅ MACRO: Vérifier si des fichiers sont présents
        if ($request->hasAnyFile(['image', 'gallery'])) {
            // Traitement spécial
        }
        
        return response()->json($savedMedia);
    }
}

// 3. Utilisation avec la Facade
use Cauri\MediaLibrary\Facades\FileUpload;

class MediaController extends Controller
{
    public function upload(Request $request)
    {
        $model = Product::find($request->model_id);
        
        // ✅ FACADE: Upload via facade
        $media = FileUpload::saveFileWithConversions(
            $request->file('upload'),
            'Fichier uploadé',
            $model,
            'uploads'
        );
        
        // ✅ FACADE: Générer des images responsives
        $responsiveImages = FileUpload::generateResponsiveImages(
            $request->file('image'),
            'product-hero',
            [320, 768, 1024, 1920]
        );
        
        return response()->json([
            'media' => $media,
            'responsive' => $responsiveImages
        ]);
    }
}

// 4. Utilisation dans des Seeders (adaptation de ta méthode saveFileSeeder)
use Cauri\MediaLibrary\Traits\FileUploadTrait;

class ProductSeeder extends Seeder
{
    use FileUploadTrait;
    
    public function run()
    {
        $products = [
            ['name' => 'Produit 1', 'image' => 'https://example.com/image1.jpg'],
            ['name' => 'Produit 2', 'image' => 'storage/seeds/image2.jpg'],
        ];
        
        foreach ($products as $productData) {
            $product = Product::create(['name' => $productData['name']]);
            
            // ✅ SEEDER: Upload depuis chemin ou URL
            $this->saveFileFromPath(
                $productData['image'],
                $productData['name'] . ' - Image',
                $product,
                'gallery'
            );
        }
    }
}

// 5. Utilisation avec les helpers
function quickUpload($file, $title = null)
{
    // ✅ HELPER: Upload simple
    return upload_file_simple($file, $title ?: 'Fichier uploadé');
}

function createDirectories()
{
    // ✅ HELPER: Créer les dossiers (adaptation de ta fonction)
    make_storage_dir();
}

// 6. Dans un modèle avec HasMedia + le trait
use Cauri\MediaLibrary\Models\Concerns\HasMedia;
use Cauri\MediaLibrary\Traits\FileUploadTrait;

class Article extends Model
{
    use HasMedia, FileUploadTrait;
    
    public function uploadFeaturedImage($file)
    {
        // Combiner HasMedia avec le trait
        return $this->saveFileWithConversions($file, 'Image vedette', $this, 'featured');
    }
    
    public function uploadGallery(array $files)
    {
        $uploadedMedia = [];
        
        foreach ($files as $file) {
            $uploadedMedia[] = $this->saveFileWithConversions(
                $file,
                'Image galerie',
                $this,
                'gallery'
            );
        }
        
        return $uploadedMedia;
    }
    
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('featured')->singleFile();
        $this->addMediaCollection('gallery');
        $this->addMediaCollection('documents');
    }
}

// 7. API Controller avec upload
class ApiMediaController extends Controller
{
    use FileUploadTrait;
    
    public function upload(Request $request)
    {
        $request->validate([
            'files.*' => 'required|file|max:10240', // 10MB
            'model_type' => 'required|string',
            'model_id' => 'required|integer',
        ]);
        
        $modelClass = $request->model_type;
        $model = $modelClass::findOrFail($request->model_id);
        
        $uploadedMedia = [];
        
        foreach ($request->file('files') as $file) {
            // Upload avec contraintes de taille
            if ($request->has('max_width') && $request->has('max_height')) {
                $processedPath = $this->processImageWithConstraints(
                    $file,
                    $request->max_width,
                    $request->max_height
                );
                
                $processedFile = new \Illuminate\Http\UploadedFile(
                    $processedPath,
                    $file->getClientOriginalName(),
                    $file->getMimeType(),
                    null,
                    true
                );
                
                $media = $this->saveFileWithConversions($processedFile, 'Image redimensionnée', $model);
                
                // Nettoyer le fichier temporaire
                unlink($processedPath);
            } else {
                $media = $this->saveFileWithConversions($file, 'Fichier uploadé', $model);
            }
            
            $uploadedMedia[] = [
                'id' => $media->id,
                'name' => $media->name,
                'url' => $media->getUrl(),
                'thumb' => $media->getUrl('thumb'),
                'size' => $media->human_readable_size,
            ];
        }
        
        return response()->json([
            'success' => true,
            'media' => $uploadedMedia
        ]);
    }
}

// 8. Configuration personnalisée dans le modèle
class CustomMediaModel extends Model
{
    use HasMedia, FileUploadTrait;
    
    protected $imageSizes = [
        ['width' => 100, 'height' => 100, 'name' => 'tiny'],
        ['width' => 300, 'height' => 300, 'name' => 'thumb'],
        ['width' => 800, 'height' => 600, 'name' => 'medium'],
        ['width' => 1600, 'height' => 1200, 'name' => 'large'],
    ];
    
    public function uploadWithCustomSizes($file, string $title)
    {
        return $this->saveFileWithCustomSizes($file, $title, $this->imageSizes, $this);
    }
    
    public function registerMediaConversions($media = null): void
    {
        foreach ($this->imageSizes as $size) {
            $this->addMediaConversion($size['name'])
                ->width($size['width'])
                ->height($size['height'])
                ->quality(85)
                ->format('webp');
        }
    }
}