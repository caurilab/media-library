<?php

namespace Cauri\MediaLibrary\Tests\Feature;

use Illuminate\Http\UploadedFile;
use Cauri\MediaLibrary\Models\Media;
use Cauri\MediaLibrary\Tests\TestCase;
use Illuminate\Support\Facades\Storage;
use Cauri\MediaLibrary\Tests\Support\TestModel;

class MediaTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        Storage::fake(config('cauri-media-library.disk_name'));
    }

    /** @test */
    public function it_can_add_media_to_a_model()
    {
        $model = TestModel::create(['name' => 'Test']);
        $file = UploadedFile::fake()->image('test.jpg', 600, 400);

        $media = $model->addMedia($file)->toMediaCollection('images');

        $this->assertInstanceOf(Media::class, $media);
        $this->assertEquals('test.jpg', $media->name);
        $this->assertEquals('images', $media->collection_name);
        $this->assertEquals('image/jpeg', $media->mime_type);
    }

    /** @test */
    public function it_can_retrieve_media_from_a_collection()
    {
        $model = TestModel::create(['name' => 'Test']);
        $file = UploadedFile::fake()->image('test.jpg');

        $model->addMedia($file)->toMediaCollection('images');

        $media = $model->getMedia('images');
        
        $this->assertCount(1, $media);
        $this->assertEquals('images', $media->first()->collection_name);
    }

    /** @test */
    public function it_can_get_the_first_media_url()
    {
        $model = TestModel::create(['name' => 'Test']);
        $file = UploadedFile::fake()->image('test.jpg');

        $model->addMedia($file)->toMediaCollection('images');

        $url = $model->getFirstMediaUrl('images');
        
        $this->assertStringContains('test.jpg', $url);
    }

    /** @test */
    public function it_can_delete_media()
    {
        $model = TestModel::create(['name' => 'Test']);
        $file = UploadedFile::fake()->image('test.jpg');

        $media = $model->addMedia($file)->toMediaCollection('images');
        $this->assertCount(1, $model->getMedia('images'));

        $media->delete();
        
        $this->assertCount(0, $model->fresh()->getMedia('images'));
    }

    /** @test */
    public function it_can_add_custom_properties()
    {
        $model = TestModel::create(['name' => 'Test']);
        $file = UploadedFile::fake()->image('test.jpg');

        $media = $model->addMedia($file)
            ->withCustomProperties(['alt' => 'Alt text', 'title' => 'Title'])
            ->toMediaCollection('images');

        $this->assertEquals('Alt text', $media->getCustomProperty('alt'));
        $this->assertEquals('Title', $media->getCustomProperty('title'));
    }

    /** @test */
    public function it_can_generate_conversions()
    {
        $model = TestModel::create(['name' => 'Test']);
        $file = UploadedFile::fake()->image('test.jpg', 1000, 800);

        $media = $model->addMedia($file)->toMediaCollection('images');
        
        // Simuler la génération de conversions
        $media->markAsConversionGenerated('thumb', true);
        $media->markAsConversionGenerated('medium', true);
        $media->save();

        $this->assertTrue($media->hasGeneratedConversion('thumb'));
        $this->assertTrue($media->hasGeneratedConversion('medium'));
    }
}