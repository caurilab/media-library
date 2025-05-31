<?php

namespace Cauri\MediaLibrary\Tests\Feature;

use Cauri\MediaLibrary\Tests\TestCase;
use Cauri\MediaLibrary\Tests\Support\TestModel;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ApiTest extends TestCase
{
    /** @test */
    public function it_can_upload_files_via_api()
    {
        $testModel = $this->createTestModel();
        $file = $this->getTestFile('test-upload.jpg');

        $response = $this->postJson('/api/cauri-media/upload', [
            'files' => [$file],
            'model_type' => get_class($testModel),
            'model_id' => $testModel->id,
            'collection' => 'images',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'media' => [
                    '*' => [
                        'id',
                        'name',
                        'url',
                        'thumb_url',
                        'size',
                        'mime_type',
                    ]
                ]
            ]
        ]);

        $this->assertTrue($response->json('success'));
        $this->assertCount(1, $response->json('data.media'));
    }

    /** @test */
    public function it_validates_upload_request()
    {
        $response = $this->postJson('/api/cauri-media/upload', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['files']);
    }

    /** @test */
    public function it_can_upload_multiple_files()
    {
        $testModel = $this->createTestModel();
        $file1 = $this->getTestFile('image1.jpg');
        $file2 = $this->getTestFile('image2.jpg');

        $response = $this->postJson('/api/cauri-media/upload', [
            'files' => [$file1, $file2],
            'model_type' => get_class($testModel),
            'model_id' => $testModel->id,
            'collection' => 'images',
        ]);

        $response->assertStatus(201);
        $this->assertCount(2, $response->json('data.media'));
    }

    /** @test */
    public function it_can_delete_media_via_api()
    {
        $testModel = $this->createTestModel();
        $file = $this->getTestFile('test-delete.jpg');
        
        $media = $testModel->addMedia($file)
            ->usingName('Test Delete')
            ->toMediaCollection('images');

        $response = $this->deleteJson("/api/cauri-media/{$media->id}");

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseMissing('media', ['id' => $media->id]);
    }

    /** @test */
    public function it_returns_404_for_non_existent_media()
    {
        $response = $this->deleteJson('/api/cauri-media/999999');

        $response->assertStatus(404);
    }

    /** @test */
    public function it_can_reorder_media_collection()
    {
        $testModel = $this->createTestModel();
        
        $media1 = $testModel->addMedia($this->getTestFile('image1.jpg'))
            ->toMediaCollection('images');
        $media2 = $testModel->addMedia($this->getTestFile('image2.jpg'))
            ->toMediaCollection('images');
        $media3 = $testModel->addMedia($this->getTestFile('image3.jpg'))
            ->toMediaCollection('images');

        $response = $this->postJson('/api/cauri-media/reorder', [
            'model_type' => get_class($testModel),
            'model_id' => $testModel->id,
            'collection' => 'images',
            'media_ids' => [$media3->id, $media1->id, $media2->id],
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Vérifier l'ordre en base
        $media1->refresh();
        $media2->refresh();
        $media3->refresh();

        $this->assertEquals(2, $media1->order_column);
        $this->assertEquals(3, $media2->order_column);
        $this->assertEquals(1, $media3->order_column);
    }

    /** @test */
    public function it_can_get_media_collection_via_api()
    {
        $testModel = $this->createTestModel();
        
        $media1 = $testModel->addMedia($this->getTestFile('image1.jpg'))
            ->toMediaCollection('gallery');
        $media2 = $testModel->addMedia($this->getTestFile('image2.jpg'))
            ->toMediaCollection('gallery');
        $media3 = $testModel->addMedia($this->getTestFile('doc.pdf'))
            ->toMediaCollection('documents');

        $response = $this->getJson("/api/cauri-media/collection/gallery?" . http_build_query([
            'model_type' => get_class($testModel),
            'model_id' => $testModel->id,
        ]));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'media',
                'pagination'
            ]
        ]);

        $media = $response->json('data.media');
        $this->assertCount(2, $media);
    }

    /** @test */
    public function it_can_search_media_via_api()
    {
        $testModel = $this->createTestModel();
        
        $media1 = $testModel->addMedia($this->getTestFile('holiday-photo.jpg'))
            ->usingName('Holiday Photo')
            ->toMediaCollection('images');
        
        $media2 = $testModel->addMedia($this->getTestFile('work-document.pdf'))
            ->usingName('Work Document')
            ->toMediaCollection('documents');

        $response = $this->getJson('/api/cauri-media/search?' . http_build_query([
            'q' => 'holiday',
        ]));

        $response->assertStatus(200);
        $data = $response->json('data');
        
        $this->assertEquals('holiday', $data['query']);
        $this->assertCount(1, $data['results']);
        $this->assertStringContainsString('Holiday', $data['results'][0]['name']);
    }

    /** @test */
    public function it_can_get_media_stats_via_api()
    {
        $testModel = $this->createTestModel();
        
        $testModel->addMedia($this->getTestFile('image1.jpg'))->toMediaCollection('images');
        $testModel->addMedia($this->getTestFile('image2.jpg'))->toMediaCollection('images');
        $testModel->addMedia($this->getTestVideoFile('video.mp4'))->toMediaCollection('videos');

        $response = $this->getJson('/api/cauri-media/stats?' . http_build_query([
            'model_type' => get_class($testModel),
            'model_id' => $testModel->id,
        ]));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'total_files',
                'total_size',
                'total_size_human',
                'by_type' => [
                    'images',
                    'videos',
                    'audio',
                    'documents',
                ],
                'by_collection',
                'recent_uploads'
            ]
        ]);

        $data = $response->json('data');
        $this->assertEquals(3, $data['total_files']);
        $this->assertEquals(2, $data['by_type']['images']);
        $this->assertEquals(1, $data['by_type']['videos']);
    }

    /** @test */
    public function it_handles_file_upload_errors_gracefully()
    {
        $testModel = $this->createTestModel();
        
        // Fichier trop gros (simulé)
        $largeFile = UploadedFile::fake()->create('large-file.jpg', 100000); // 100MB

        $response = $this->postJson('/api/cauri-media/upload', [
            'files' => [$largeFile],
            'model_type' => get_class($testModel),
            'model_id' => $testModel->id,
        ]);

        $response->assertStatus(422);
        $this->assertFalse($response->json('success'));
    }

    /** @test */
    public function it_requires_valid_model_for_upload()
    {
        $file = $this->getTestFile('test.jpg');

        $response = $this->postJson('/api/cauri-media/upload', [
            'files' => [$file],
            'model_type' => TestModel::class,
            'model_id' => 999999, // ID inexistant
        ]);

        $response->assertStatus(422);
    }
}