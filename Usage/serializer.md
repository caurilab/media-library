```php
// Repository
$repository = new MediaRepository();
$media = $repository->getImages($product);

// Serialization
$serializer = new MediaCollectionSerializer();
$apiData = $serializer->toApiResponse($media);
$vueData = $serializer->toVueFormat($media);

// Video thumbnails
$videoGenerator = new Video();
if ($videoGenerator->canHandle($media->mime_type)) {
    $thumbnail = $videoGenerator->convert($media, $conversion);
}

//////////////////

## Repository
$repo = new MediaRepository();
$images = $repo->getImages($product);
$results = $repo->search('photo', $product);
$stats = $repo->getCountByType($product);

## Serializer

$collection = $product->getMedia('videos');
$apiData = $collection->toApiResponse(); // Pour API
$vueData = $collection->toVueFormat();   // Pour Vue.js
$gallery = $collection->toGalleryFormat(); // Pour galerie

## Video Thumbnails

// Automatique avec FFmpeg si dispo
$thumbnail = $videoGenerator->convert($media, $conversion);

// Ou placeholder si FFmpeg indisponible
```