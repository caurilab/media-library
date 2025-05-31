<?php

namespace Cauri\MediaLibrary\Tests\Unit;

use Cauri\MediaLibrary\Tests\TestCase;
use Cauri\MediaLibrary\Tests\Support\TestModel;
use Cauri\MediaLibrary\PathGenerator\DefaultPathGenerator;
use Cauri\MediaLibrary\Models\Media;

class PathGeneratorTest extends TestCase
{
    protected DefaultPathGenerator $pathGenerator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pathGenerator = new DefaultPathGenerator();
    }

    /** @test */
    public function it_generates_correct_path_for_original_file()
    {
        $testModel = TestModel::create(['name' => 'Test Model']);
        
        $media = Media::create([
            'model_type' => get_class($testModel),
            'model_id' => $testModel->id,
            'name' => 'Test Image',
            'file_name' => 'test-image.jpg',
            'mime_type' => 'image/jpeg',
            'disk' => 'media',
            'size' => 1024,
            'collection_name' => 'gallery',
        ]);

        $path = $this->pathGenerator->getPath($media);
        
        $expectedPath = "TestModel/{$testModel->id}/gallery/{$media->id}/test-image.jpg";
        $this->assertEquals($expectedPath, $path);
    }

    /** @test */
    public function it_generates_correct_path_for_conversion()
    {
        $testModel = TestModel::create(['name' => 'Test Model']);
        
        $media = Media::create([
            'model_type' => get_class($testModel),
            'model_id' => $testModel->id,
            'name' => 'Test Image',
            'file_name' => 'test-image.jpg',
            'mime_type' => 'image/jpeg',
            'disk' => 'media',
            'size' => 1024,
            'collection_name' => 'gallery',
        ]);

        $path = $this->pathGenerator->getPath($media, 'thumb');
        
        $expectedPath = "TestModel/{$testModel->id}/gallery/{$media->id}/conversions/test-image-thumb.webp";
        $this->assertEquals($expectedPath, $path);
    }

    /** @test */
    public function it_generates_conversion_directory_path()
    {
        $testModel = TestModel::create(['name' => 'Test Model']);
        
        $media = Media::create([
            'model_type' => get_class($testModel),
            'model_id' => $testModel->id,
            'name' => 'Test Image',
            'file_name' => 'test-image.jpg',
            'mime_type' => 'image/jpeg',
            'disk' => 'media',
            'size' => 1024,
            'collection_name' => 'gallery',
        ]);

        $path = $this->pathGenerator->getPathForConversions($media);
        
        $expectedPath = "TestModel/{$testModel->id}/gallery/{$media->id}/conversions/";
        $this->assertEquals($expectedPath, $path);
    }

    /** @test */
    public function it_sanitizes_model_class_name()
    {
        // Test avec un namespace complexe
        $media = Media::create([
            'model_type' => 'App\\Models\\Complex\\ProductCategory',
            'model_id' => 1,
            'name' => 'Test',
            'file_name' => 'test.jpg',
            'mime_type' => 'image/jpeg',
            'disk' => 'media',
            'size' => 1024,
            'collection_name' => 'default',
        ]);

        $path = $this->pathGenerator->getPath($media);
        
        // Le nom de classe devrait être simplifié
        $this->assertStringContainsString('ProductCategory/', $path);
        $this->assertStringNotContainsString('App\\', $path);
    }

    /** @test */
    public function it_handles_different_file_extensions_for_conversions()
    {
        $testModel = TestModel::create(['name' => 'Test Model']);
        
        $media = Media::create([
            'model_type' => get_class($testModel),
            'model_id' => $testModel->id,
            'name' => 'Test Image',
            'file_name' => 'test-image.png',
            'mime_type' => 'image/png',
            'disk' => 'media',
            'size' => 1024,
            'collection_name' => 'gallery',
        ]);

        $path = $this->pathGenerator->getPath($media, 'thumb');
        
        // Les conversions devraient être en WebP par défaut
        $this->assertStringEndsWith('test-image-thumb.webp', $path);
    }

    /** @test */
    public function it_handles_files_without_extension()
    {
        $testModel = TestModel::create(['name' => 'Test Model']);
        
        $media = Media::create([
            'model_type' => get_class($testModel),
            'model_id' => $testModel->id,
            'name' => 'Test File',
            'file_name' => 'testfile',
            'mime_type' => 'application/octet-stream',
            'disk' => 'media',
            'size' => 1024,
            'collection_name' => 'documents',
        ]);

        $path = $this->pathGenerator->getPath($media);
        
        $expectedPath = "TestModel/{$testModel->id}/documents/{$media->id}/testfile";
        $this->assertEquals($expectedPath, $path);
    }

    /** @test */
    public function it_creates_unique_paths_for_different_media()
    {
        $testModel = TestModel::create(['name' => 'Test Model']);
        
        $media1 = Media::create([
            'model_type' => get_class($testModel),
            'model_id' => $testModel->id,
            'name' => 'Image 1',
            'file_name' => 'image1.jpg',
            'mime_type' => 'image/jpeg',
            'disk' => 'media',
            'size' => 1024,
            'collection_name' => 'gallery',
        ]);

        $media2 = Media::create([
            'model_type' => get_class($testModel),
            'model_id' => $testModel->id,
            'name' => 'Image 2',
            'file_name' => 'image2.jpg',
            'mime_type' => 'image/jpeg',
            'disk' => 'media',
            'size' => 1024,
            'collection_name' => 'gallery',
        ]);

        $path1 = $this->pathGenerator->getPath($media1);
        $path2 = $this->pathGenerator->getPath($media2);
        
        $this->assertNotEquals($path1, $path2);
        $this->assertStringContainsString((string)$media1->id, $path1);
        $this->assertStringContainsString((string)$media2->id, $path2);
    }
}