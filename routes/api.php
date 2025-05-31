<?php

use Illuminate\Support\Facades\Route;
use Cauri\MediaLibrary\Http\Controllers\Api\MediaApiController;

// Media Management Routes
Route::group(['prefix' => 'media'], function () {
    // Upload
    Route::post('/upload', [MediaApiController::class, 'upload'])->name('media.upload');
    Route::post('/upload-url', [MediaApiController::class, 'uploadFromUrl'])->name('media.upload-url');
    
    // Media Operations
    Route::get('/{media}', [MediaApiController::class, 'show'])->name('media.show');
    Route::patch('/{media}', [MediaApiController::class, 'update'])->name('media.update');
    Route::delete('/{media}', [MediaApiController::class, 'destroy'])->name('media.destroy');
    
    // Collections
    Route::get('/collection/{collection}', [MediaApiController::class, 'getCollection'])->name('media.collection');
    Route::post('/reorder', [MediaApiController::class, 'reorder'])->name('media.reorder');
    
    // Conversions
    Route::get('/{media}/conversions', [MediaApiController::class, 'getConversions'])->name('media.conversions');
    Route::post('/{media}/regenerate', [MediaApiController::class, 'regenerateConversions'])->name('media.regenerate');
    
    // Bulk Operations
    Route::post('/bulk-delete', [MediaApiController::class, 'bulkDelete'])->name('media.bulk-delete');
    Route::post('/bulk-update', [MediaApiController::class, 'bulkUpdate'])->name('media.bulk-update');
    
    // Search & Filter
    Route::get('/search', [MediaApiController::class, 'search'])->name('media.search');
    Route::get('/stats', [MediaApiController::class, 'getStats'])->name('media.stats');
});