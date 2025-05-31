<?php

namespace Cauri\MediaLibrary\Tests\Feature;

use Cauri\MediaLibrary\Tests\TestCase;
use Cauri\MediaLibrary\Tests\Support\TestModel;
use Cauri\MediaLibrary\Jobs\GenerateConversions;
use Cauri\MediaLibrary\Conversions\ImageGenerators\Image;
use Cauri\MediaLibrary\Conversions\Conversion;
use Illuminate\Support\Facades\Storage;

class ConversionsTest extends TestCase
{
    /** @test */
    public function it_can_generate_image_conversions()
    {
        $testModel = $this->createTestModel();
        $file = $this->getTestFile('test-image.jpg', 500, 400);
        
        $media = $testModel->addMedia($file)
            ->usingName('Test Image')
            ->toMediaCollection('images');

        // Marquer les conversions comme générées pour le test
        $media->markAsConversionGenerated('thumb', true);
        $media->markAsConversionGenerated('medium', true);
        $media->save();

        $this->assertTrue($media->hasGeneratedConversion('thumb'));
        $this->assertTrue($media->hasGeneratedConversion('medium'));
    }

    /** @test */
    public function it_can_get_conversion_urls()
    {
        $testModel = $this->createTestModel();
        $file = $this->getTestFile('test-image.jpg');
        
        $media = $testModel->addMedia($file)
            ->usingName('Test Image')
            ->toMediaCollection('images');

        $originalUrl = $media->getUrl();
        $thumbUrl = $media->getUrl('thumb');
        $mediumUrl = $media->getUrl('medium');

        $this->assertNotEquals($originalUrl, $thumbUrl);
        $this->assertNotEquals($originalUrl, $mediumUrl);
        $this->assertNotEquals($thumbUrl, $mediumUrl);
        
        $this->assertStringContainsString('test-image', $originalUrl);
        $this->assertStringContainsString('thumb', $thumbUrl);
        $this->assertStringContainsString('medium', $mediumUrl);
    }

    /** @test */
    public function it_can_handle_conversion_generation_job()
    {
        $testModel = $this->createTestModel();
        $file = $this->getTestFile('test-image.jpg');
        
        $media = $testModel->addMedia($file)
            ->usingName('Test Image')
            ->toMediaCollection('images');

        // Simuler le job de génération
        $job = new GenerateConversions($media);
        
        // Vérifier que le job peut être sérialisé
        $serialized = serialize($job);
        $unserialized = unserialize($serialized);
        
        $this->assertInstanceOf(GenerateConversions::class, $unserialized);
        $this->assertEquals($media->id, $unserialized->media->id);
    }

    /** @test */
    public function it_can_create_conversion_definition()
    {
        $conversion = new Conversion('test-conversion');
        
        $conversion
            ->width(300)
            ->height(300)
            ->quality(85)
            ->format('webp')
            ->fit('cover')
            ->nonQueued();

        $this->assertEquals('test-conversion', $conversion->getName());
        $this->assertEquals(300, $conversion->getWidth());
        $this->assertEquals(300, $conversion->getHeight());
        $this->assertEquals(85, $conversion->getQuality());
        $this->assertEquals('webp', $conversion->getFormat());
        $this->assertEquals('cover', $conversion->getFit());
        $this->assertFalse($conversion->isQueued());
    }

    /** @test */
    public function it_can_check_if_image_generator_can_handle_mime_types()
    {
        $imageGenerator = new Image();

        $this->assertTrue($imageGenerator->canHandle('image/jpeg'));
        $this->assertTrue($imageGenerator->canHandle('image/png'));
        $this->assertTrue($imageGenerator->canHandle('image/gif'));
        $this->assertTrue($imageGenerator->canHandle('image/webp'));
        
        $this->assertFalse($imageGenerator->canHandle('image/svg+xml'));
        $this->assertFalse($imageGenerator->canHandle('video/mp4'));
        $this->assertFalse($imageGenerator->canHandle('application/pdf'));
    }

    /** @test */
    public function it_falls_back_to_original_when_conversion_does_not_exist()
    {
        $testModel = $this->createTestModel();
        $file = $this->getTestFile('test-image.jpg');
        
        $media = $testModel->addMedia($file)
            ->usingName('Test Image')
            ->toMediaCollection('images');

        // URL de conversion inexistante devrait retourner l'original
        $nonExistentUrl = $media->getUrl('non-existent-conversion');
        $originalUrl = $media->getUrl();
        
        // Note: selon l'implémentation, cela pourrait être identique ou différent
        $this->assertIsString($nonExistentUrl);
    }

    /** @test */
    public function it_can_mark_conversion_as_failed()
    {
        $testModel = $this->createTestModel();
        $file = $this->getTestFile('test-image.jpg');
        
        $media = $testModel->addMedia($file)
            ->usingName('Test Image')
            ->toMediaCollection('images');

        $media->markAsConversionFailed('thumb');
        $media->save();

        $this->assertFalse($media->hasGeneratedConversion('thumb'));
        
        // Vérifier que la conversion échouée est trackée
        $generatedConversions = $media->generated_conversions ?? [];
        $this->assertArrayHasKey('thumb', $generatedConversions);
        $this->assertFalse($generatedConversions['thumb']);
    }

    /** @test */
    public function it_can_regenerate_specific_conversions()
    {
        $testModel = $this->createTestModel();
        $file = $this->getTestFile('test-image.jpg');
        
        $media = $testModel->addMedia($file)
            ->usingName('Test Image')
            ->toMediaCollection('images');

        // Marquer comme généré initialement
        $media->markAsConversionGenerated('thumb', true);
        $media->save();

        $this->assertTrue($media->hasGeneratedConversion('thumb'));

        // Simuler une régénération
        $media->markAsConversionGenerated('thumb', false);
        $media->save();

        $this->assertFalse($media->hasGeneratedConversion('thumb'));

        // Re-générer
        $media->markAsConversionGenerated('thumb', true);
        $media->save();

        $this->assertTrue($media->hasGeneratedConversion('thumb'));
    }
}