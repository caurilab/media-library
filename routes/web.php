<?php

use Illuminate\Support\Facades\Route;
use Cauri\MediaLibrary\Http\Controllers\MediaController;

// Web Routes for Admin/Management Interface
Route::group(['prefix' => 'admin/media', 'middleware' => ['web']], function () {
    Route::get('/', [MediaController::class, 'index'])->name('admin.media.index');
    Route::get('/gallery', [MediaController::class, 'gallery'])->name('admin.media.gallery');
    Route::get('/{media}/preview', [MediaController::class, 'preview'])->name('admin.media.preview');
});