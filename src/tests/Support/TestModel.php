<?php

namespace Cauri\MediaLibrary\Tests\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Cauri\MediaLibrary\Models\Concerns\HasMedia;
use Cauri\MediaLibrary\Models\Media;

class TestModel extends Model
{
    use HasMedia;

    protected $table = 'test_models';
    protected $fillable = ['name', 'description'];
    public $timestamps = false;

    public static function migrateUp(): void
    {
        Schema::create('test_models', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
        });
    }

    public static function migrateDown(): void
    {
        Schema::dropIfExists('test_models');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('images')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/gif']);

        $this->addMediaCollection('documents')
            ->acceptsMimeTypes(['application/pdf'])
            ->singleFile();

        $this->addMediaCollection('videos')
            ->acceptsMimeTypes(['video/mp4']);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(150)
            ->height(150)
            ->quality(80)
            ->nonQueued();

        $this->addMediaConversion('medium')
            ->width(500)
            ->height(500)
            ->quality(85)
            ->nonQueued();
    }
}