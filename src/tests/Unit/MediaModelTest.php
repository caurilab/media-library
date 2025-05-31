<?php

namespace Cauri\MediaLibrary\Tests\Unit;

use Cauri\MediaLibrary\Tests\TestCase;
use Cauri\MediaLibrary\Tests\Support\TestModel;
use Cauri\MediaLibrary\Models\Media;
use Illuminate\Support\Str;

class MediaModelTest extends TestCase
{
    /** @test */
    public function it_can_create_a_media_record()
    {
        $media = Media::create([
            'name' => 'Test Image',
            'file_name' => 'test.jpg',
            'mime_type' => 'image/jpeg',
            'disk' => 'media',
            'size' => 1024,
            'collection_name' => 'default',
        ]);

        $this->assertInstanceOf(Media::class, $media);
        $this->assertEquals('Test Image', $media->name);
        $this->assertEquals('test.jpg', $media->file_name);
        $this->assertEquals('image/jpeg', $media->mime_type);
        $this->assertTrue(Str::isUuid($media->uuid));
    }

    /** @test */
    public function it_generates_uuid_automatically()
    {
        $media = Media::create([
            'name' => 'Test',
            'file_name' => 'test.jpg',
            'mime_type' => 'image/jpeg',
            'disk' => 'media',
            'size' => 1024,
        ]);

        $this->assertNotNull($media->uuid);
        $this->assertTrue(Str::isUuid($media->uuid));
    }

    /** @test */
    public function it_can_determine_file_types()
    {
        $imageMedia = Media::create([
            'name' => 'Image',
            'file_name' => 'image.jpg',
            'mime_type' => 'image/jpeg',
            'disk' => 'media',
            'size' => 1024,
        ]);

        $videoMedia = Media::create([
            'name' => 'Video',
            'file_name' => 'video.mp4',
            'mime_type' => 'video/mp4',
            'disk' => 'media',
            'size' => 2048,
        ]);

        $pdfMedia = Media::create([
            'name' => 'Document',
            'file_name' => 'doc.pdf',
            'mime_type' => 'application/pdf',
            'disk' => 'media',
            'size' => 512,
        ]);

        $this->assertTrue($imageMedia->isImage());
        $this->assertFalse($imageMedia->isVideo());
        $this->assertEquals('image', $imageMedia->type);

        $this->assertTrue($videoMedia->isVideo());
        $this->assertFalse($videoMedia->isImage());
        $this->assertEquals('video', $videoMedia->type);

        $this->assertTrue($pdfMedia->isPdf());
        $this->assertEquals('document', $pdfMedia->type);
    }

    /** @test */
    public function it_can_handle_custom_properties()
    {
        $media = Media::create([
            'name' => 'Test',
            'file_name' => 'test.jpg',
            'mime_type' => 'image/jpeg',
            'disk' => 'media',
            'size' => 1024,
        ]);

        $media->setCustomProperty('alt', 'Alternative text');
        $media->setCustomProperty('caption', 'Image caption');
        $media->save();

        $this->assertEquals('Alternative text', $media->getCustomProperty('alt'));
        $this->assertEquals('Image caption', $media->getCustomProperty('caption'));
        $this->assertNull($media->getCustomProperty('non_existing'));
        $this->assertEquals('default', $media->getCustomProperty('non_existing', 'default'));
    }

    /** @test */
    public function it_can_track_generated_conversions()
    {
        $media = Media::create([
            'name' => 'Test',
            'file_name' => 'test.jpg',
            'mime_type' => 'image/jpeg',
            'disk' => 'media',
            'size' => 1024,
        ]);

        $this->assertFalse($media->hasGeneratedConversion('thumb'));

        $media->markAsConversionGenerated('thumb', true);
        $media->save();

        $this->assertTrue($media->hasGeneratedConversion('thumb'));
        $this->assertFalse($media->hasGeneratedConversion('medium'));
    }

    /** @test */
    public function it_can_generate_human_readable_size()
    {
        $media = Media::create([
            'name' => 'Test',
            'file_name' => 'test.jpg',
            'mime_type' => 'image/jpeg',
            'disk' => 'media',
            'size' => 1024,
        ]);

        $this->assertEquals('1.00 kB', $media->human_readable_size);

        $media->size = 1024 * 1024;
        $this->assertEquals('1.00 MB', $media->human_readable_size);
    }

    /** @test */
    public function it_can_get_file_extension()
    {
        $media = Media::create([
            'name' => 'Test',
            'file_name' => 'test.jpg',
            'mime_type' => 'image/jpeg',
            'disk' => 'media',
            'size' => 1024,
        ]);

        $this->assertEquals('jpg', $media->extension);
    }

    /** @test */
    public function it_belongs_to_a_model()
    {
        $testModel = TestModel::create(['name' => 'Test Model']);
        
        $media = Media::create([
            'model_type' => get_class($testModel),
            'model_id' => $testModel->id,
            'name' => 'Test',
            'file_name' => 'test.jpg',
            'mime_type' => 'image/jpeg',
            'disk' => 'media',
            'size' => 1024,
        ]);

        $this->assertInstanceOf(TestModel::class, $media->model);
        $this->assertEquals($testModel->id, $media->model->id);
    }

    /** @test */
    public function it_can_scope_by_images()
    {
        Media::create([
            'name' => 'Image',
            'file_name' => 'image.jpg',
            'mime_type' => 'image/jpeg',
            'disk' => 'media',
            'size' => 1024,
        ]);

        Media::create([
            'name' => 'Video',
            'file_name' => 'video.mp4',
            'mime_type' => 'video/mp4',
            'disk' => 'media',
            'size' => 2048,
        ]);

        $images = Media::images()->get();
        
        $this->assertCount(1, $images);
        $this->assertEquals('image.jpg', $images->first()->file_name);
    }

    /** @test */
    public function it_can_scope_by_collection()
    {
        Media::create([
            'name' => 'Gallery Image',
            'file_name' => 'gallery.jpg',
            'mime_type' => 'image/jpeg',
            'disk' => 'media',
            'size' => 1024,
            'collection_name' => 'gallery',
        ]);

        Media::create([
            'name' => 'Avatar Image',
            'file_name' => 'avatar.jpg',
            'mime_type' => 'image/jpeg',
            'disk' => 'media',
            'size' => 512,
            'collection_name' => 'avatars',
        ]);

        $galleryImages = Media::inCollection('gallery')->get();
        
        $this->assertCount(1, $galleryImages);
        $this->assertEquals('gallery.jpg', $galleryImages->first()->file_name);
    }
}