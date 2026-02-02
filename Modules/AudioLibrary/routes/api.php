<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    
    // Admin routes
    Route::prefix('admin')
        ->middleware([
            'auth:sanctum',
            'admin',
            \Modules\Authentication\Http\Middleware\RequireTwoFactorSetup::class,
            'module.feature:AudioLibrary'
        ])
        ->group(function () {
            Route::get('audio-library/stats', [\Modules\AudioLibrary\Http\Controllers\Api\V1\Admin\AudioLibraryController::class, 'stats'])
                ->name('admin.audio-library.stats')
                ->middleware('permission:view_audio_stats');
            
            Route::get('audio-library/users-with-uploads', [\Modules\AudioLibrary\Http\Controllers\Api\V1\Admin\AudioLibraryController::class, 'usersWithUploads'])
                ->name('admin.audio-library.users-with-uploads')
                ->middleware('permission:view_audio_stats');
            
            Route::get('audio-library/audio', [\Modules\AudioLibrary\Http\Controllers\Api\V1\Admin\AudioLibraryController::class, 'index'])
                ->name('admin.audio-library.audio')
                ->middleware('permission:view_all_audio');
            
            Route::delete('audio-library/audio/{id}', [\Modules\AudioLibrary\Http\Controllers\Api\V1\Admin\AudioLibraryController::class, 'destroy'])
                ->name('admin.audio-library.audio.delete')
                ->middleware('permission:delete_any_audio');
        });

    // Customer routes
    Route::prefix('customer')
        ->middleware([
            'auth:sanctum',
            \Modules\Authentication\Http\Middleware\RequireTwoFactorSetup::class,
            'module.feature:AudioLibrary',
        ])
        ->group(function () {
            // Audio operations
            Route::post('audio-library/upload', [\Modules\AudioLibrary\Http\Controllers\Api\V1\Customer\AudioLibraryController::class, 'upload'])
                ->name('customer.audio-library.upload')
                ->middleware('permission:upload_audio');
            
            Route::post('audio-library/bulk-upload', [\Modules\AudioLibrary\Http\Controllers\Api\V1\Customer\AudioLibraryController::class, 'bulkUpload'])
                ->name('customer.audio-library.bulk-upload')
                ->middleware('permission:bulk_upload_audio');
            
            // Browse shared library (read-only; use_audio_library)
            Route::get('audio-library/browse/folders', [\Modules\AudioLibrary\Http\Controllers\Api\V1\Customer\AudioLibraryController::class, 'browseFolders'])
                ->name('customer.audio-library.browse.folders')
                ->middleware('permission:use_audio_library');
            Route::get('audio-library/browse', [\Modules\AudioLibrary\Http\Controllers\Api\V1\Customer\AudioLibraryController::class, 'browseIndex'])
                ->name('customer.audio-library.browse')
                ->middleware('permission:use_audio_library');
            Route::get('audio-library/browse/{id}', [\Modules\AudioLibrary\Http\Controllers\Api\V1\Customer\AudioLibraryController::class, 'browseShow'])
                ->name('customer.audio-library.browse.show')
                ->middleware('permission:use_audio_library');

            Route::get('audio-library/audio', [\Modules\AudioLibrary\Http\Controllers\Api\V1\Customer\AudioLibraryController::class, 'index'])
                ->name('customer.audio-library.audio')
                ->middleware('permission:view_audio');
            
            Route::get('audio-library/audio/{id}', [\Modules\AudioLibrary\Http\Controllers\Api\V1\Customer\AudioLibraryController::class, 'show'])
                ->name('customer.audio-library.audio.show')
                ->middleware('permission:view_audio');
            
            Route::put('audio-library/audio/{id}', [\Modules\AudioLibrary\Http\Controllers\Api\V1\Customer\AudioLibraryController::class, 'update'])
                ->name('customer.audio-library.audio.update')
                ->middleware('permission:manage_audio');
            
            Route::delete('audio-library/audio/{id}', [\Modules\AudioLibrary\Http\Controllers\Api\V1\Customer\AudioLibraryController::class, 'destroy'])
                ->name('customer.audio-library.audio.delete')
                ->middleware('permission:manage_audio');
            
            // Folders
            Route::get('audio-library/folders', [\Modules\AudioLibrary\Http\Controllers\Api\V1\Customer\AudioLibraryController::class, 'listFolders'])
                ->name('customer.audio-library.folders')
                ->middleware('permission:manage_audio_folders');
            
            Route::post('audio-library/folders', [\Modules\AudioLibrary\Http\Controllers\Api\V1\Customer\AudioLibraryController::class, 'createFolder'])
                ->name('customer.audio-library.folders.create')
                ->middleware('permission:manage_audio_folders');
            
            Route::put('audio-library/folders/{id}', [\Modules\AudioLibrary\Http\Controllers\Api\V1\Customer\AudioLibraryController::class, 'updateFolder'])
                ->name('customer.audio-library.folders.update')
                ->middleware('permission:manage_audio_folders');
            
            Route::delete('audio-library/folders/{id}', [\Modules\AudioLibrary\Http\Controllers\Api\V1\Customer\AudioLibraryController::class, 'deleteFolder'])
                ->name('customer.audio-library.folders.delete')
                ->middleware('permission:manage_audio_folders');
            
            // Upload queue status
            Route::get('audio-library/upload-queue/{id}', [\Modules\AudioLibrary\Http\Controllers\Api\V1\Customer\AudioLibraryController::class, 'getUploadQueueStatus'])
                ->name('customer.audio-library.upload-queue')
                ->middleware('permission:upload_audio');
        });
});
