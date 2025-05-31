<?php

namespace Cauri\MediaLibrary;

use League\Flysystem\Filesystem;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Filesystem\FilesystemAdapter;
use Cauri\MediaLibrary\Commands\CleanCommand;
use Cauri\MediaLibrary\Commands\RegenerateCommand;
use Cauri\MediaLibrary\FileAdder\FileAdderFactory;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Cauri\MediaLibrary\Extensions\RequestExtension;
use Cauri\MediaLibrary\UrlGenerator\DefaultUrlGenerator;
use Cauri\MediaLibrary\PathGenerator\DefaultPathGenerator;

class CauriMediaLibraryServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->bootPublishing();
        $this->bootRoutes();
        $this->bootViews();
        $this->bootCommands();
        $this->bootFilesystem();
        $this->bootHelpers();
        $this->bootMacros();
    }

    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/cauri-media-library.php',
            'cauri-media-library'
        );

        $this->registerBindings();
        $this->registerFacades();

        $this->app->singleton('cauri-file-upload', function () {
            return new FileUploadManager();
        });
    }

    protected function bootPublishing(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/cauri-media-library.php' => config_path('cauri-media-library.php'),
            ], 'cauri-media-config');

            $this->publishes([
                __DIR__.'/../database/migrations/' => database_path('migrations'),
            ], 'cauri-media-migrations');

            $this->publishes([
                __DIR__.'/../resources/js/' => resource_path('js/cauri-media'),
            ], 'cauri-media-vue');

            $this->publishes([
                __DIR__.'/../resources/css/' => resource_path('css/cauri-media'),
            ], 'cauri-media-css');

            $this->publishes([
                __DIR__.'/../resources/views/' => resource_path('views/vendor/cauri-media'),
            ], 'cauri-media-views');

            $this->publishes([
                __DIR__.'/../stubs/' => base_path('stubs/cauri-media'),
            ], 'cauri-media-stubs');
        }
    }

    protected function bootRoutes(): void
    {
        if (config('cauri-media-library.api.prefix')) {
            Route::group([
                'prefix' => 'api/' . config('cauri-media-library.api.prefix'),
                'middleware' => config('cauri-media-library.api.middleware', ['web']),
                'namespace' => 'Cauri\\MediaLibrary\\Http\\Controllers\\Api',
            ], function () {
                $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
            });
        }

        Route::group([
            'middleware' => ['web'],
            'namespace' => 'Cauri\\MediaLibrary\\Http\\Controllers',
        ], function () {
            $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        });
    }

    protected function bootViews(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'cauri-media');
    }

    protected function bootCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                RegenerateCommand::class,
                CleanCommand::class,
            ]);
        }
    }

    protected function bootFilesystem(): void
    {
        $this->app->afterResolving('filesystem', function ($filesystem) {
            if (!$filesystem->disk(config('cauri-media-library.disk_name'))) {
                $filesystem->extend(config('cauri-media-library.disk_name'), function ($app, $config) {
                    return new FilesystemAdapter(
                        new Filesystem(
                            new LocalFilesystemAdapter(storage_path('app/media'))
                        ),
                        $config
                    );
                });
            }
        });
    }

    protected function registerBindings(): void
    {
        $this->app->bind('cauri-media-library', function () {
            return new MediaLibrary();
        });

        $this->app->bind(
            config('cauri-media-library.path_generator'),
            DefaultPathGenerator::class
        );

        $this->app->bind(
            config('cauri-media-library.url_generator'),
            DefaultUrlGenerator::class
        );

        $this->app->singleton(FileAdderFactory::class);
    }

    protected function registerFacades(): void
    {
        $this->app->alias('cauri-media-library', \Cauri\MediaLibrary\Facades\MediaLibrary::class);
    }

    public function provides(): array
    {
        return [
            'cauri-media-library',
            FileAdderFactory::class,
        ];
    }

    protected function bootHelpers(): void
    {
        require_once __DIR__ . '/Support/helpers.php';
    }
    
    protected function bootMacros(): void
    {
        RequestExtension::extend();
    }
}