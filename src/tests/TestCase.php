<?php

namespace Cauri\MediaLibrary\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Cauri\MediaLibrary\CauriMediaLibraryServiceProvider;
use Cauri\MediaLibrary\Tests\Support\TestModel;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpDatabase();
        $this->setUpStorage();
        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Cauri\\MediaLibrary\\Tests\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function tearDown(): void
    {
        $this->cleanUpStorage();
        parent::tearDown();
    }

    protected function getPackageProviders($app)
    {
        return [
            CauriMediaLibraryServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app)
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        // Setup filesystem for testing
        $app['config']->set('filesystems.default', 'media');
        $app['config']->set('filesystems.disks.media', [
            'driver' => 'local',
            'root' => storage_path('framework/testing/media'),
            'url' => env('APP_URL').'/storage/media',
            'visibility' => 'public',
        ]);

        // Setup media library config
        $app['config']->set('cauri-media-library.disk_name', 'media');
        $app['config']->set('cauri-media-library.queue_conversions_by_default', false);
    }

    protected function setUpDatabase(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        
        // Create test model table
        $this->artisan('migrate', ['--database' => 'testbench']);
        
        $this->beforeApplicationDestroyed(function () {
            $this->artisan('migrate:rollback', ['--database' => 'testbench']);
        });
    }

    protected function setUpStorage(): void
    {
        Storage::fake('media');
    }

    protected function cleanUpStorage(): void
    {
        Storage::disk('media')->deleteDirectory('');
    }

    /**
     * Create a test uploaded file
     */
    protected function getTestFile(string $name = 'test.jpg', int $width = 100, int $height = 100): UploadedFile
    {
        return UploadedFile::fake()->image($name, $width, $height);
    }

    /**
     * Create a test video file
     */
    protected function getTestVideoFile(string $name = 'test.mp4'): UploadedFile
    {
        return UploadedFile::fake()->create($name, 1000, 'video/mp4');
    }

    /**
     * Create a test PDF file
     */
    protected function getTestPdfFile(string $name = 'test.pdf'): UploadedFile
    {
        return UploadedFile::fake()->create($name, 500, 'application/pdf');
    }

    /**
     * Create a test model
     */
    protected function createTestModel(array $attributes = []): TestModel
    {
        return TestModel::create(array_merge(['name' => 'Test Model'], $attributes));
    }

    /**
     * Assert that a media file exists on disk
     */
    protected function assertMediaExists(string $path): void
    {
        $this->assertTrue(
            Storage::disk('media')->exists($path),
            "Media file does not exist at path: {$path}"
        );
    }

    /**
     * Assert that a media file does not exist on disk
     */
    protected function assertMediaNotExists(string $path): void
    {
        $this->assertFalse(
            Storage::disk('media')->exists($path),
            "Media file should not exist at path: {$path}"
        );
    }

    /**
     * Assert that a conversion exists
     */
    protected function assertConversionExists(\Cauri\MediaLibrary\Models\Media $media, string $conversionName): void
    {
        $this->assertTrue(
            $media->hasGeneratedConversion($conversionName),
            "Conversion '{$conversionName}' does not exist for media {$media->id}"
        );

        $conversionPath = $media->getPath($conversionName);
        $this->assertMediaExists($conversionPath);
    }

    /**
     * Get test image path
     */
    protected function getTestImagePath(): string
    {
        $path = __DIR__ . '/fixtures/test-image.jpg';
        
        if (!file_exists($path)) {
            // Create a simple test image if it doesn't exist
            $this->createTestImageFile($path);
        }

        return $path;
    }

    /**
     * Create a test image file
     */
    protected function createTestImageFile(string $path): void
    {
        $directory = dirname($path);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        // Create a simple 100x100 red image
        $image = imagecreate(100, 100);
        $red = imagecolorallocate($image, 255, 0, 0);
        imagefill($image, 0, 0, $red);
        imagejpeg($image, $path);
        imagedestroy($image);
    }
}