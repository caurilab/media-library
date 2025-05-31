<?php

namespace Cauri\MediaLibrary\Models\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Cauri\MediaLibrary\Models\Media;

class MediaFactory extends Factory
{
    protected $model = Media::class;

    public function definition(): array
    {
        $fileName = $this->faker->word . '.' . $this->faker->fileExtension();
        
        return [
            'collection_name' => 'default',
            'name' => $this->faker->words(3, true),
            'file_name' => $fileName,
            'mime_type' => $this->faker->mimeType(),
            'disk' => config('cauri-media-library.disk_name'),
            'size' => $this->faker->numberBetween(1024, 1024 * 1024 * 10), // 1KB to 10MB
            'custom_properties' => [],
            'generated_conversions' => [],
            'responsive_images' => [],
            'order_column' => $this->faker->numberBetween(1, 100),
            'uuid' => Str::uuid(),
        ];
    }

    public function image(): static
    {
        return $this->state(fn (array $attributes) => [
            'file_name' => $this->faker->word . '.jpg',
            'mime_type' => 'image/jpeg',
        ]);
    }

    public function video(): static
    {
        return $this->state(fn (array $attributes) => [
            'file_name' => $this->faker->word . '.mp4',
            'mime_type' => 'video/mp4',
        ]);
    }

    public function pdf(): static
    {
        return $this->state(fn (array $attributes) => [
            'file_name' => $this->faker->word . '.pdf',
            'mime_type' => 'application/pdf',
        ]);
    }
}