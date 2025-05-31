<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            
            // Relations polymorphiques
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            
            // Informations du fichier
            $table->string('collection_name')->default('default');
            $table->string('name');
            $table->string('file_name');
            $table->string('mime_type')->nullable();
            $table->string('disk');
            $table->unsignedBigInteger('size');
            
            // Métadonnées
            $table->json('custom_properties')->nullable();
            $table->json('generated_conversions')->nullable();
            $table->json('responsive_images')->nullable();
            
            // Ordre dans la collection
            $table->unsignedInteger('order_column')->nullable();
            
            // Hash pour éviter les doublons (optionnel)
            $table->string('sha1_hash')->nullable();
            
            // UUID pour les URLs sécurisées (optionnel)
            $table->uuid('uuid')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Index pour les performances
            $table->index(['model_type', 'model_id'], 'media_model_index');
            $table->index('collection_name');
            $table->index('mime_type');
            $table->index('sha1_hash');
            $table->index('uuid');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};